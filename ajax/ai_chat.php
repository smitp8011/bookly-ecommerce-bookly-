<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Parse input
$input_raw = file_get_contents('php://input');
$data = json_decode($input_raw, true);

$message = trim($data['message'] ?? ($_POST['message'] ?? ''));
if ($message === '') {
    echo json_encode(['ok' => false, 'message' => 'Empty message.']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// 1. Fetch current catalog for RAG context
$books_stmt = $pdo->query(
    'SELECT b.id, b.title, b.author, b.price, b.stock, b.cover_image, b.description,
            COALESCE(c.name, "General") AS category_name,
            COALESCE(AVG(r.rating), 0) AS avg_rating,
            COUNT(r.id) AS review_count
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     LEFT JOIN reviews r ON r.book_id = b.id
     GROUP BY b.id'
);
$catalog = $books_stmt->fetchAll();

// 2. Fetch active coupons
$coupons_stmt = $pdo->query("SELECT code, discount_type, discount_value, min_order_value FROM coupons WHERE active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())");
$active_coupons = $coupons_stmt->fetchAll();

$lower = strtolower($message);
$matched_cards = [];
$order_card = null;
$reply_text = '';

// -------------------------------------------------------------
// 0. PROFANITY & MODERATION LAYER
// -------------------------------------------------------------
if (preg_match('/\b(?:mother\s*fucker|mf|fuck|fucking|fucker|bitch|bastard|asshole|dick|pussy|slut|whore|shit|damn|crap|chutiya|chutiye|madarchod|behenchod|bhenchod|gandu|harami|kamina|kamine|saale|saala|kutte|kuttiya|bhosdike|bhosadi|laude|lodu|chodu|idiot|stupid|bakwas|nonsense|rubbish|shut up)\b/i', $lower)) {
    $reply_text = "⚠️ Kripya aadar aur shalinata se baat karein! 😊 (Please keep the conversation polite and respectful.)\n\nMain yahan aapki **Book Recommendations**, **Order Tracking**, **Discounts**, aur **Book Reading** me madad karne ke liye hoon. Bataiye, mai aapki kya help kar sakta hoon? 📚";
    echo json_encode(['ok' => true, 'reply' => $reply_text, 'cards' => []]);
    exit;
}

// -------------------------------------------------------------
// 1. DETERMINISTIC ORDER LOOKUP (If explicit Order ID is asked)
// -------------------------------------------------------------
if (preg_match('/(?:order|tracking|track|status|kaha he|kahan hai|kab aayega)\s*(?:#|no\.?|id|number)?\s*(\d+)/i', $message, $m) || preg_match('/#(\d+)/', $message, $m)) {
    $order_id = (int)$m[1];
    $order_stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $order_stmt->execute([$order_id]);
    $order = $order_stmt->fetch();

    if ($order) {
        $placed = date('M j, Y', strtotime($order['placed_on']));
        $status = ucfirst($order['status']);
        $total = price($order['total_price']);
        
        $order_card = [
            'id' => $order['id'],
            'status' => $order['status'],
            'placed_on' => $placed,
            'total' => $total,
            'payment' => ucfirst($order['payment_method'])
        ];

        $reply_text = "I looked up **Order #{$order['id']}** for you!\n\n" .
                      "• **Status**: `{$status}`\n" .
                      "• **Placed On**: {$placed}\n" .
                      "• **Total Amount**: {$total}\n" .
                      "• **Payment Method**: {$order_card['payment']}\n\n";

        if ($order['status'] === 'delivered') {
            $reply_text .= "🎉 Your package has been successfully delivered! We hope you enjoy reading your books.";
        } elseif ($order['status'] === 'shipped') {
            $reply_text .= "🚚 Your package is currently in transit with our express courier partner and is expected to arrive within 1-2 business days.";
        } elseif ($order['status'] === 'processing') {
            $reply_text .= "📦 Your books are currently being packed with care at our central archive warehouse.";
        } else {
            $reply_text .= "⏳ Your order has been placed and is waiting for warehouse verification.";
        }

        echo json_encode([
            'ok' => true,
            'reply' => $reply_text,
            'cards' => [],
            'order_card' => $order_card
        ]);
        exit;
    }
}

// -------------------------------------------------------------
// 2. PRIMARY GENERATIVE LLM: GOOGLE GEMINI REAL AI
// -------------------------------------------------------------
if (!empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
    try {
        $gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=' . GEMINI_API_KEY;

        $catalog_summary = [];
        foreach ($catalog as $b) {
            $catalog_summary[] = "ID: {$b['id']} | Title: \"{$b['title']}\" by {$b['author']} | Category: {$b['category_name']} | Price: " . price($b['price']) . " | Synopsis: " . substr(strip_tags($b['description']), 0, 120);
        }

        $coupons_summary = [];
        foreach ($active_coupons as $cp) {
            $val = $cp['discount_type'] === 'percent' ? "{$cp['discount_value']}% OFF" : price($cp['discount_value']) . " OFF";
            $coupons_summary[] = "Code: '{$cp['code']}' ({$val}, Min Order: " . price($cp['min_order_value']) . ")";
        }

        $system_instruction = "You are 'BookNest AI', a friendly, warm, witty, and exceptionally knowledgeable bookstore assistant and reading concierge for 'BookNest' (an online book ordering and delivery platform for physical books).\n\n" .
                               "STORE INVENTORY CATALOG:\n" . implode("\n", $catalog_summary) . "\n\n" .
                               "ACTIVE COUPONS:\n" . (empty($coupons_summary) ? "No active coupons." : implode(", ", $coupons_summary)) . "\n\n" .
                               "STORE POLICIES:\n" .
                               "- Standard Delivery: 2-4 business days across India. FREE shipping on orders above ₹499.\n" .
                               "- Returns: 7-day hassle-free replacement.\n" .
                               "- Payment: Cash on Delivery (COD).\n" .
                               "- Guest Browsing: Supported (browse & add to cart without login; login needed at checkout).\n\n" .
                               "INSTRUCTIONS:\n" .
                               "1. You are a real Generative AI. Understand and answer ANY question naturally (philosophies, book summaries, jokes, reading tips, casual chit-chat, advice, etc.).\n" .
                               "2. Respond in the same language or dialect the user uses (English, Hindi, Hinglish, etc.) with a warm, courteous and engaging tone.\n" .
                               "3. If recommending books, prioritize our catalog books and mention their titles in quotes (e.g. \"Red Queen\").\n" .
                               "4. Keep responses natural, conversational, and nicely structured using markdown.";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $system_instruction . "\n\nUser Message: " . $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 800
            ]
        ];

        $ch = curl_init($gemini_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 9
        ]);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $res) {
            $res_json = json_decode($res, true);
            $ai_text = $res_json['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (!empty($ai_text)) {
                $reply_text = trim($ai_text);

                // Auto-detect which books from catalog were mentioned by AI to attach interactive cards
                $ai_lower = strtolower($reply_text);
                foreach ($catalog as $b) {
                    $b_title = strtolower($b['title']);
                    if (str_contains($ai_lower, $b_title) || (strlen($b_title) > 6 && str_contains($ai_lower, substr($b_title, 0, 8)))) {
                        $matched_cards[] = [
                            'id' => (int)$b['id'],
                            'title' => $b['title'],
                            'author' => $b['author'],
                            'category' => $b['category_name'],
                            'price' => price($b['price']),
                            'raw_price' => (float)$b['price'],
                            'cover' => 'images/' . $b['cover_image'],
                            'rating' => round((float)$b['avg_rating'], 1),
                            'stock' => (int)$b['stock']
                        ];
                    }
                    if (count($matched_cards) >= 3) break;
                }

                // If user specifically asked for recommendations/budget and AI didn't explicitly name books, attach top matching picks
                if (empty($matched_cards) && preg_match('/(?:recommend|suggest|book|kitab|under|sasti|budget|buy|genre)/i', $lower)) {
                    $matched_cards = array_map(fn($b) => [
                        'id' => (int)$b['id'],
                        'title' => $b['title'],
                        'author' => $b['author'],
                        'category' => $b['category_name'],
                        'price' => price($b['price']),
                        'raw_price' => (float)$b['price'],
                        'cover' => 'images/' . $b['cover_image'],
                        'rating' => round((float)$b['avg_rating'], 1),
                        'stock' => (int)$b['stock']
                    ], array_slice($catalog, 0, 2));
                }

                echo json_encode([
                    'ok' => true,
                    'reply' => $reply_text,
                    'cards' => $matched_cards,
                    'order_card' => null
                ]);
                exit;
            }
        }
    } catch (Exception $e) {
        // Fallback to local engine if network fails
    }
}

// -------------------------------------------------------------
// 3. GROK API (IF GROK KEY CONFIGURED)
// -------------------------------------------------------------
if (!empty(GROK_API_KEY)) {
    try {
        $grok_url = 'https://api.x.ai/v1/chat/completions';
        $payload = [
            'model' => 'grok-beta',
            'messages' => [
                ['role' => 'system', 'content' => "You are BookNest AI, a witty and literary bookstore assistant for BookNest. Help readers discover great books."],
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 200
        ];
        $ch = curl_init($grok_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GROK_API_KEY
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 8
        ]);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $res) {
            $res_json = json_decode($res, true);
            $reply_text = $res_json['choices'][0]['message']['content'] ?? '';
            if ($reply_text) {
                echo json_encode(['ok' => true, 'reply' => $reply_text, 'cards' => []]);
                exit;
            }
        }
    } catch (Exception $e) {
        // Fallback
    }
}

// -------------------------------------------------------------
// 4. RESILIENT LOCAL NLP FALLBACK (If offline or no API key)
// -------------------------------------------------------------
if (empty($reply_text)) {
    if (preg_match('/(?:coupon|discount|promo|code|offer|deal|save|saving|chhut|off)/i', $lower)) {
        if (!empty($active_coupons)) {
            $reply_text = "Here are the active discount codes available right now at **BookNest**! 🎉\n\n";
            foreach ($active_coupons as $cp) {
                $val = $cp['discount_type'] === 'percent' ? (float)$cp['discount_value'] . '% OFF' : price($cp['discount_value']) . ' OFF';
                $reply_text .= "• **`{$cp['code']}`** — **{$val}** (Min spend: " . price($cp['min_order_value']) . ")\n";
            }
            $reply_text .= "\nYou can apply any of these codes directly in your [Cart](cart.php) before checkout!";
        } else {
            $reply_text = "We don't have any active public coupon codes right now, but all orders above **₹499** automatically qualify for **FREE Express Delivery**! 🚚";
        }
    } else {
        $reply_text = "👋 Hello! I'm **BookNest AI**, your reading concierge. I'm here to help you explore our library, read free sample chapters, track orders, and discover great stories!\n\n" .
                      "How can I help you in your reading journey today?";
        
        $matched_cards = array_map(fn($b) => [
            'id' => (int)$b['id'],
            'title' => $b['title'],
            'author' => $b['author'],
            'category' => $b['category_name'],
            'price' => price($b['price']),
            'raw_price' => (float)$b['price'],
            'cover' => 'images/' . $b['cover_image'],
            'rating' => round((float)$b['avg_rating'], 1),
            'stock' => (int)$b['stock']
        ], array_slice($catalog, 0, 2));
    }
}

echo json_encode([
    'ok' => true,
    'reply' => $reply_text,
    'cards' => $matched_cards,
    'order_card' => $order_card
]);
