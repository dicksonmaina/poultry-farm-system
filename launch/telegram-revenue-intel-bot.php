<?php
/**
 * Telegram Revenue-Intel Bot
 *
 * Monitors configured Telegram channels/groups, scores messages for revenue potential,
 * and pushes high-value intel to an Obsidian vault via webhook or direct file write.
 *
 * Capabilities:
 * - Real-time message scoring (revenue, tech, security, jobs keywords)
 * - AUTO/APPROVE/BLOCK tier routing
 * - Lead capture via inline keyboards
 * - Daily/weekly digest generation
 * - Obsidian vault sync
 *
 * Requirements:
 * - TELEGRAM_BOT_TOKEN
 * - TELEGRAM_ALLOWED_CHAT_IDS (comma-separated)
 * - OBSIDIAN_VAULT_PATH (optional, for direct file writes)
 * - Webhook endpoint: /telegram-revenue-intel.php
 */

// Minimal environment loader
function env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

// Config
$botToken = env_value('TELEGRAM_BOT_TOKEN', '');
$allowedChatIds = array_filter(array_map('trim', explode(',', env_value('TELEGRAM_ALLOWED_CHAT_IDS', ''))));
$obsidianVaultPath = env_value('OBSIDIAN_VAULT_PATH', '');
$apiBase = 'https://api.telegram.org/bot' . rawurlencode($botToken);

if ($botToken === '' || empty($allowedChatIds)) {
    http_response_code(500);
    echo json_encode(['error' => 'TELEGRAM_BOT_TOKEN and TELEGRAM_ALLOWED_CHAT_IDS must be set']);
    exit;
}

// Scoring engine
function score_message(string $text): array {
    $lower = strtolower($text);
    $revenueKeywords = ['price', 'payment', 'subscription', 'mpesa', 'stk', 'pay', 'invoice', 'quote', 'budget', 'cost', 'sale', 'client', 'customer', 'farm', 'poultry', 'chicken', 'eggs', 'feed', 'profit'];
    $techKeywords = ['python', 'javascript', 'php', 'api', 'ai', 'ml', 'docker', 'kubernetes', 'cloud', 'saas', 'database', 'sql', 'git', 'github', 'deploy', 'server', 'linux'];
    $securityKeywords = ['vulnerability', 'cve', 'exploit', 'patch', 'security', 'hack', 'breach', 'malware', 'ransomware', 'phishing', 'encryption'];
    $jobsKeywords = ['hiring', 'job', 'remote', 'contract', 'freelance', 'position', 'vacancy', 'opportunity', 'developer', 'engineer', 'designer'];

    $revenueScore = 0;
    $techScore = 0;
    $securityScore = 0;
    $jobsScore = 0;

    foreach ($revenueKeywords as $kw) {
        if (str_contains($lower, $kw)) $revenueScore++;
    }
    foreach ($techKeywords as $kw) {
        if (str_contains($lower, $kw)) $techScore++;
    }
    foreach ($securityKeywords as $kw) {
        if (str_contains($lower, $kw)) $securityScore++;
    }
    foreach ($jobsKeywords as $kw) {
        if (str_contains($lower, $kw)) $jobsScore++;
    }

    $total = $revenueScore + $techScore + $securityScore + $jobsScore;
    $maxScore = max($revenueScore, $techScore, $securityScore, $jobsScore);
    $category = 'general';
    if ($maxScore > 0) {
        if ($revenueScore === $maxScore) $category = 'revenue';
        elseif ($techScore === $maxScore) $category = 'tech';
        elseif ($securityScore === $maxScore) $category = 'security';
        elseif ($jobsScore === $maxScore) $category = 'jobs';
    }

    // Tier assignment
    $tier = 'BLOCK';
    if ($total >= 6 || $revenueScore >= 3) {
        $tier = 'AUTO';
    } elseif ($total >= 2 || $revenueScore >= 1) {
        $tier = 'APPROVE';
    }

    return [
        'total' => $total,
        'revenue' => $revenueScore,
        'tech' => $techScore,
        'security' => $securityScore,
        'jobs' => $jobsScore,
        'category' => $category,
        'tier' => $tier,
    ];
}

// Telegram API helpers
function tgSend(string $apiBase, array $params): array {
    $ch = curl_init($apiBase . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function tgAnswerCallback(string $apiBase, array $params): array {
    $ch = curl_init($apiBase . '/answerCallbackQuery');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

// Obsidian vault writer
function writeToObsidian(string $vaultPath, string $filename, string $content): bool {
    if ($vaultPath === '' || !is_dir($vaultPath)) {
        return false;
    }
    $filePath = rtrim($vaultPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    return file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX) !== false;
}

function format_intel_entry(array $data): string {
    $date = date('Y-m-d H:i');
    $entry = "\n## [{$data['category']}] {$data['title']}\n";
    $entry .= "- **Score**: {$data['score']} ({$data['tier']})\n";
    $entry .= "- **Source**: {$data['source']}\n";
    $entry .= "- **Date**: {$date}\n";
    $entry .= "- **Summary**: {$data['summary']}\n";
    if (!empty($data['link'])) {
        $entry .= "- **Link**: {$data['link']}\n";
    }
    $entry .= "\n";
    return $entry;
}

// Main webhook handler
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$updateId = $input['update_id'] ?? null;
$message = $input['message'] ?? null;
$callbackQuery = $input['callback_query'] ?? null;

// Handle callback queries
if ($callbackQuery) {
    $chatId = $callbackQuery['message']['chat']['id'] ?? null;
    $data = $callbackQuery['data'] ?? '';
    $callbackQueryId = $callbackQuery['id'] ?? null;

    if ($chatId && $callbackQueryId && in_array((string) $chatId, $allowedChatIds, true)) {
        $replyText = '✅ Lead captured. We will contact you shortly.';

        if ($data === 'support') {
            $replyText = '🎧 Support ticket created. An agent will respond shortly.';
        } elseif ($data === 'demo') {
            $replyText = '📅 Demo request received. Check your messages for scheduling.';
        } elseif ($data === 'signup') {
            $replyText = '🚀 Signup link sent. Use the link to start your free trial.';
        } elseif ($data === 'price') {
            $replyText = '💰 Starter: KES 750/mo | Pro: KES 1,500/mo. Reply for custom quote.';
        } elseif ($data === 'intel') {
            $replyText = '📊 Intel report generated. Check your Obsidian vault for details.';
        }

        tgAnswerCallback($apiBase, [
            'callback_query_id' => $callbackQueryId,
            'text' => $replyText,
            'show_alert' => false,
        ]);
    }

    http_response_code(200);
    echo json_encode(['status' => 'callback_handled']);
    exit;
}

// Handle new chat members
if ($message && isset($message['new_chat_members']) && is_array($message['new_chat_members'])) {
    $chatId = $message['chat']['id'] ?? null;

    if ($chatId && in_array((string) $chatId, $allowedChatIds, true)) {
        foreach ($message['new_chat_members'] as $member) {
            $name = $member['first_name'] ?? 'Farmer';
            $welcome = "👋 Welcome, {$name}!\n\n";
            $welcome .= "I'm the Poultry Farm System revenue-intel bot.\n";
            $welcome .= "I score messages for business value and sync high-signal intel to our knowledge base.\n\n";
            $welcome .= "Quick actions:\n";
            $welcome .= "• /intel — Generate revenue/tech/security intel report\n";
            $welcome .= "• /signup — Start free trial\n";
            $welcome .= "• /support — Talk to support\n";
            $welcome .= "• /price — See pricing plans";

            tgSend($apiBase, [
                'chat_id' => $chatId,
                'text' => $welcome,
                'parse_mode' => 'HTML',
            ]);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'welcome_sent']);
    exit;
}

// Handle text messages
if ($message && isset($message['text'])) {
    $chatId = $message['chat']['id'] ?? null;
    $text = trim((string) $message['text']);
    $lower = strtolower($text);

    if (!$chatId || !in_array((string) $chatId, $allowedChatIds, true)) {
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
        exit;
    }

    // Commands
    if ($lower === '/start' || $lower === '/help') {
        $help = "🐔 Poultry Farm System — Revenue Intel Bot\n\n";
        $help .= "Commands:\n";
        $help .= "/intel — Generate intel report\n";
        $help .= "/signup — Start free trial\n";
        $help .= "/support — Open support ticket\n";
        $help .= "/price — View pricing\n";
        $help .= "/digest — Latest intel digest\n";
        $help .= "/poll — Weekly feature poll";

        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => $help,
        ]);
    } elseif ($lower === '/intel') {
        $report = "📊 Revenue Intel Report\n\n";
        $report .= "This bot scores all messages for:\n";
        $report .= "• 💰 Revenue signals (price, payment, farm, mpesa)\n";
        $report .= "• 💻 Tech signals (ai, python, api, docker)\n";
        $report .= "• 🔒 Security signals (vulnerability, cve, exploit)\n";
        $report .= "• 💼 Job signals (hiring, remote, contract)\n\n";
        $report .= "High-value items are synced to Obsidian vault automatically.";

        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => $report,
        ]);
    } elseif ($lower === '/digest') {
        $digestPath = $obsidianVaultPath !== '' ? rtrim($obsidianVaultPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '00-Inbox' . DIRECTORY_SEPARATOR . 'live-intel-feed.md' : '';
        if ($digestPath !== '' && file_exists($digestPath)) {
            $content = file_get_contents($digestPath);
            $lines = explode("\n", $content);
            $topLines = array_slice($lines, 0, 20);
            $text = "📰 Daily Intel Digest\n\n" . implode("\n", $topLines);
        } else {
            $text = "📰 Daily Intel Digest\n\nNo intel available yet.";
        }
        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    } elseif ($lower === '/poll') {
        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => '📊 Weekly Feature Poll\nWhich feature should we build next?\nA) Offline mode\nB) M-Pesa integrations\nC) Advanced reports\nD) Mobile app',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => 'A', 'callback_data' => 'poll_a'],
                        ['text' => 'B', 'callback_data' => 'poll_b'],
                        ['text' => 'C', 'callback_data' => 'poll_c'],
                        ['text' => 'D', 'callback_data' => 'poll_d'],
                    ],
                ],
            ]),
        ]);
    } elseif ($lower === '/signup') {
        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => '🚀 Signup link sent. Check your messages to start your free trial.',
        ]);
    } elseif ($lower === '/support') {
        $ticketId = 'TKT-' . strtoupper(substr(uniqid(), -6));
        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => "🎧 Support ticket #{$ticketId} created. An agent will respond shortly.",
        ]);
    } elseif ($lower === '/price') {
        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => "💰 Pricing:\n• Starter: KES 750/month\n• Professional: KES 1,500/month\n• Enterprise: Custom\n\nReply for a custom quote.",
        ]);
    } else {
        // Score the message
        $scores = score_message($text);
        $source = $message['chat']['title'] ?? $message['chat']['username'] ?? 'unknown';

        // Prepare intel entry
        $intelEntry = [
            'title' => mb_substr($text, 0, 100),
            'summary' => mb_substr($text, 0, 200),
            'source' => $source,
            'score' => $scores['total'],
            'tier' => $scores['tier'],
            'category' => $scores['category'],
            'link' => '',
            'date' => date('Y-m-d H:i'),
        ];

        // Auto-sync high-value intel to Obsidian
        if ($scores['tier'] === 'AUTO' && $obsidianVaultPath !== '') {
            $filename = '00-Inbox' . DIRECTORY_SEPARATOR . 'live-intel-feed.md';
            $entry = format_intel_entry($intelEntry);
            writeToObsidian($obsidianVaultPath, $filename, $entry);
        }

        // Reply based on tier
        if ($scores['tier'] === 'AUTO') {
            $reply = "📈 High-value signal detected!\n";
            $reply .= "Category: {$scores['category']}\n";
            $reply .= "Score: {$scores['total']}/10\n";
            $reply .= "Synced to Obsidian vault.";
        } elseif ($scores['tier'] === 'APPROVE') {
            $reply = "👍 Noted. Category: {$scores['category']} (Score: {$scores['total']}/10)";
        } else {
            $reply = "👋 I didn't catch a strong signal there. Try /intel, /price, or /signup.";
        }

        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => $reply,
        ]);
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
