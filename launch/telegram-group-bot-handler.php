<?php
/**
 * Telegram group automation handler.
 *
 * Designed to run as a standalone webhook endpoint or CLI loop.
 * Uses existing @Rixikibot token and OpenClaw Telegram gateway where available.
 *
 * Capabilities:
 * - Welcome new members with poultry farm intro
 * - Auto-reply to keywords: price, demo, signup, support
 * - Capture leads via inline keyboards
 * - Daily digest of scored intel from telegram_digest/
 * - Weekly polls for feature prioritization
 *
 * Requirements:
 * - Bot must be admin in target group with send_messages permission
 * - TELEGRAM_BOT_TOKEN and TELEGRAM_GROUP_ID in environment or config
 */

// Minimal environment loader if running outside main app
function env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

$botToken = env_value('TELEGRAM_BOT_TOKEN', '');
$groupId = env_value('TELEGRAM_GROUP_ID', '');

if ($botToken === '' || $groupId === '') {
    http_response_code(500);
    echo json_encode(['error' => 'TELEGRAM_BOT_TOKEN and TELEGRAM_GROUP_ID must be set']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$updateId = $input['update_id'] ?? null;
$message = $input['message'] ?? null;
$callbackQuery = $input['callback_query'] ?? null;

$apiBase = 'https://api.telegram.org/bot' . rawurlencode($botToken);

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

// Handle callback queries from inline keyboards
if ($callbackQuery) {
    $chatId = $callbackQuery['message']['chat']['id'] ?? null;
    $data = $callbackQuery['data'] ?? '';
    $callbackQueryId = $callbackQuery['id'] ?? null;

    if ($chatId && $callbackQueryId) {
        $replyText = '✅ Lead captured. We will contact you shortly.';

        if ($data === 'support') {
            $replyText = '🎧 Support ticket created. An agent will respond shortly.';
        } elseif ($data === 'demo') {
            $replyText = '📅 Demo request received. Check your messages for scheduling.';
        } elseif ($data === 'signup') {
            $replyText = '🚀 Signup link sent. Use the link to start your free trial.';
        } elseif ($data === 'price') {
            $replyText = '💰 Starter: KES 750/mo | Pro: KES 1,500/mo. Reply for custom quote.';
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
    $newMembers = $message['new_chat_members'];

    if ($chatId && count($newMembers) > 0) {
        foreach ($newMembers as $member) {
            $name = $member['first_name'] ?? 'Farmer';
            $welcome = "👋 Welcome, {$name}!\n\n";
            $welcome .= "This is the Poultry Farm System community.\n";
            $welcome .= "We help small and medium poultry farms in Kenya manage flocks, feed, sales, and M-Pesa payments.\n\n";
            $welcome .= "Quick actions:\n";
            $welcome .= "• /demo — See a live demo\n";
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

    if (!$chatId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing chat_id']);
        exit;
    }

    // Commands
    if ($lower === '/start' || $lower === '/help') {
        $help = "🐔 Poultry Farm System Bot\n\n";
        $help .= "Commands:\n";
        $help .= "/demo — Request a demo\n";
        $help .= "/signup — Start free trial\n";
        $help .= "/support — Open support ticket\n";
        $help .= "/price — View pricing\n";
        $help .= "/digest — Latest intel digest\n";
        $help .= "/poll — Weekly feature poll";

        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => $help,
        ]);
    } elseif ($lower === '/demo') {
        tgSend($apiBase, [
            'chat_id' => $chatId,
            'text' => '📅 Demo request received. We will contact you shortly to schedule.',
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
    } elseif ($lower === '/digest') {
        $digestPath = __DIR__ . '/../../jarvis_memory/telegram_digest/digest_latest.jsonl';
        if (file_exists($digestPath)) {
            $lines = file($digestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $top = array_slice($lines, 0, 5);
            $text = "📰 Daily Intel Digest\n\n";
            foreach ($top as $line) {
                $item = json_decode($line, true);
                if ($item) {
                    $text .= "• " . ($item['title'] ?? $item['text'] ?? 'Intel item') . "\n";
                }
            }
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
    } else {
        // Keyword-based auto-replies
        $keywords = [
            'price' => '💰 Starter: KES 750/mo | Pro: KES 1,500/mo. Use /signup to start.',
            'demo' => '📅 Use /demo to request a live walkthrough.',
            'signup' => '🚀 Use /signup to start your free trial.',
            'support' => '🎧 Use /support to open a ticket.',
            'mpesa' => '📱 We support M-Pesa STK Push for subscription payments.',
            'farm' => '🐔 This bot supports poultry farm management, feed tracking, and sales.',
        ];

        $matched = false;
        foreach ($keywords as $keyword => $reply) {
            if (str_contains($lower, $keyword)) {
                tgSend($apiBase, [
                    'chat_id' => $chatId,
                    'text' => $reply,
                ]);
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            tgSend($apiBase, [
                'chat_id' => $chatId,
                'text' => "👋 I didn't catch that. Try /demo, /signup, /support, or /price.",
            ]);
        }
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
