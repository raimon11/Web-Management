<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db.php';
$currentUserId = (int)$_SESSION['user_id'];
$RECEIVER_ID   = 1;

// ── AJAX: upload image ─────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'upload_image') {
    header('Content-Type: application/json');
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        echo json_encode(['success' => false, 'error' => 'No file received.']);
        exit();
    }
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $ftype   = mime_content_type($_FILES['image']['tmp_name']);
    if (!in_array($ftype, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid image type.']);
        exit();
    }
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Image too large (max 5MB).']);
        exit();
    }
    $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'msg_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $dir      = 'uploads/messages/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        echo json_encode(['success' => true, 'url' => $dest]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save image.']);
    }
    exit();
}

// ── AJAX: poll for new messages ────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'poll') {
    header('Content-Type: application/json');
    $afterId = (int)($_GET['after_id'] ?? 0);

    $stmt = mysqli_prepare($conn, "
        SELECT `message_id`, `sender_id`, `receiver_id`, `message_text`,
               `is_read`, `created_at`, `updated_at`, `image_url`
        FROM `messages`
        WHERE ((`sender_id` = ? AND `receiver_id` = ?)
            OR (`sender_id` = ? AND `receiver_id` = ?))
          AND `message_id` > ?
        ORDER BY `created_at` ASC
    ");
    mysqli_stmt_bind_param($stmt, 'iiiii',
        $currentUserId, $RECEIVER_ID,
        $RECEIVER_ID,   $currentUserId,
        $afterId
    );
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result($stmt);
    $newMsgs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    if (!empty($newMsgs)) {
        $mark = mysqli_prepare($conn, "
            UPDATE `messages` SET `is_read` = 1, `updated_at` = NOW()
            WHERE `sender_id` = ? AND `receiver_id` = ? AND `is_read` = 0
        ");
        mysqli_stmt_bind_param($mark, 'ii', $RECEIVER_ID, $currentUserId);
        mysqli_stmt_execute($mark);
        mysqli_stmt_close($mark);
    }

    echo json_encode($newMsgs);
    exit();
}

// ── AJAX: send message ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $text     = trim($_POST['message_text'] ?? '');
    $imageUrl = trim($_POST['image_url']    ?? '') ?: null;

    if ($text === '' && $imageUrl === null) {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit();
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO `messages` (`sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`, `updated_at`, `image_url`)
        VALUES (?, ?, ?, 0, NOW(), NOW(), ?)
    ");
    mysqli_stmt_bind_param($stmt, 'iiss', $currentUserId, $RECEIVER_ID, $text, $imageUrl);
    mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $row = mysqli_prepare($conn, "
        SELECT `message_id`, `sender_id`, `receiver_id`, `message_text`,
               `is_read`, `created_at`, `updated_at`, `image_url`
        FROM `messages` WHERE `message_id` = ?
    ");
    mysqli_stmt_bind_param($row, 'i', $newId);
    mysqli_stmt_execute($row);
    $result  = mysqli_stmt_get_result($row);
    $message = mysqli_fetch_assoc($result);
    mysqli_stmt_close($row);

    echo json_encode(['success' => true, 'message' => $message]);
    exit();
}

// ── Page load: fetch conversation ─────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT `message_id`, `sender_id`, `receiver_id`, `message_text`,
           `is_read`, `created_at`, `updated_at`, `image_url`
    FROM `messages`
    WHERE (`sender_id` = ? AND `receiver_id` = ?)
       OR (`sender_id` = ? AND `receiver_id` = ?)
    ORDER BY `created_at` ASC
");
mysqli_stmt_bind_param($stmt, 'iiii', $currentUserId, $RECEIVER_ID, $RECEIVER_ID, $currentUserId);
mysqli_stmt_execute($stmt);
$result     = mysqli_stmt_get_result($stmt);
$dbMessages = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$markRead = mysqli_prepare($conn, "
    UPDATE `messages` SET `is_read` = 1, `updated_at` = NOW()
    WHERE `sender_id` = ? AND `receiver_id` = ? AND `is_read` = 0
");
mysqli_stmt_bind_param($markRead, 'ii', $RECEIVER_ID, $currentUserId);
mysqli_stmt_execute($markRead);
mysqli_stmt_close($markRead);

$lastMessageId = !empty($dbMessages) ? (int)end($dbMessages)['message_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COZIEST - Messages</title>
    <link rel="stylesheet" href="./styles/index.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --red:        #c22b1b;
            --red-dark:   #a82416;
            --red-glow:   rgba(194, 43, 27, 0.18);
            --bg-page:    #f2f0ec;
            --bg-card:    #ffffff;
            --bg-chat:    #f5f3ef;
            --border:     #e8e5df;
            --text-main:  #1c1917;
            --text-muted: #9b9490;
            --bubble-in:  #ffffff;
        }

        body.dashboard-page { font-family: 'DM Sans', 'Segoe UI', sans-serif; }

        .main {
            flex: 1; display: flex; flex-direction: column;
            height: 100vh; overflow: hidden; padding: 0;
            background: var(--bg-page);
        }

        .messenger-container {
            flex: 1; display: flex;
            height: 100vh; padding: 20px; overflow: hidden;
        }

        .messenger-card {
            display: flex; flex-direction: column; flex: 1;
            background: var(--bg-card); border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04), 0 12px 40px rgba(0,0,0,0.10);
            border: 1px solid var(--border); height: 100%;
        }

        /* Header */
        .chat-header {
            padding: 14px 20px; background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
            flex-shrink: 0; position: relative;
        }
        .chat-header::after {
            content: ''; position: absolute;
            bottom: -1px; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--red) 0%, transparent 60%);
            opacity: 0.35;
        }
        .back-icon {
            font-size: 18px; color: var(--text-muted); cursor: pointer;
            width: 34px; height: 34px; display: flex; align-items: center;
            justify-content: center; border-radius: 10px;
            transition: background 0.18s, color 0.18s;
            text-decoration: none; flex-shrink: 0;
        }
        .back-icon:hover { background: var(--bg-page); color: var(--text-main); }
        .header-avatar-wrap { position: relative; flex-shrink: 0; }
        .header-avatar {
            width: 40px; height: 40px; border-radius: 14px;
            background: linear-gradient(140deg, #e8796e 0%, var(--red) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: #fff; letter-spacing: 0.5px;
            box-shadow: 0 3px 10px var(--red-glow);
        }
        .online-ring {
            position: absolute; bottom: -1px; right: -1px;
            width: 11px; height: 11px; background: #22c55e;
            border-radius: 50%; border: 2px solid #fff;
        }
        .header-info { flex: 1; min-width: 0; }
        .header-info h2 {
            font-size: 14px; font-weight: 700; color: var(--text-main);
            margin: 0 0 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .subtitle {
            font-size: 11px; color: #22c55e; font-weight: 600;
            display: flex; align-items: center; gap: 4px; margin: 0;
        }
        .active-dot {
            width: 6px; height: 6px; background: #22c55e;
            border-radius: 50%; flex-shrink: 0;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.75); }
        }
        .header-actions { display: flex; gap: 4px; }
        .header-action-btn {
            width: 34px; height: 34px; border-radius: 10px; border: none;
            background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; color: var(--text-muted); margin: 0; padding: 0;
            transition: background 0.18s, color 0.18s;
        }
        .header-action-btn:hover { background: var(--bg-page); color: var(--text-main); }

        /* Messages area */
        .chat-messages {
            flex: 1; overflow-y: auto; padding: 24px 22px 12px;
            background: var(--bg-chat);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            display: flex; flex-direction: column; gap: 4px; scroll-behavior: smooth;
        }
        .chat-messages::-webkit-scrollbar { width: 5px; }
        .chat-messages::-webkit-scrollbar-track { background: transparent; }
        .chat-messages::-webkit-scrollbar-thumb { background: #d8d4cd; border-radius: 99px; }
        .chat-messages::-webkit-scrollbar-thumb:hover { background: #bfbab2; }

        .date-separator {
            display: flex; align-items: center; gap: 10px; margin: 14px 0 10px;
        }
        .date-separator::before, .date-separator::after { content: ''; flex: 1; height: 1px; background: #dedad4; }
        .date-separator span {
            font-size: 10.5px; color: #b0aa9f; font-weight: 600;
            letter-spacing: 0.6px; text-transform: uppercase;
            white-space: nowrap; background: var(--bg-chat); padding: 0 6px;
        }

        .empty-chat {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 14px; padding: 40px 20px;
        }
        .empty-chat-icon {
            width: 64px; height: 64px; border-radius: 20px;
            background: linear-gradient(135deg, #f0ede8, #e8e0d8);
            display: flex; align-items: center; justify-content: center; font-size: 28px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        .empty-chat h3 { font-size: 15px; font-weight: 700; color: #7a746d; margin: 0; }
        .empty-chat p  { font-size: 13px; color: #b0aa9f; text-align: center; margin: 0; }

        .message-row {
            display: flex; width: 100%; align-items: flex-end; gap: 8px;
            animation: msgIn 0.22s cubic-bezier(0.34, 1.2, 0.64, 1) both;
        }
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(8px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .message-row.user  { justify-content: flex-end; }
        .message-row.other { justify-content: flex-start; }
        .message-row + .message-row.user,
        .message-row + .message-row.other { margin-top: 2px; }
        .message-row.user  + .message-row.other,
        .message-row.other + .message-row.user  { margin-top: 10px; }

        .bubble {
            max-width: 100%; padding: 10px 14px;
            font-size: 14px; line-height: 1.55; word-wrap: break-word; position: relative;
        }
        .message-row.user .bubble {
            background: linear-gradient(135deg, #d63a29 0%, var(--red) 100%);
            color: #fff;
            border-radius: 20px 20px 5px 20px;
            box-shadow: 0 3px 14px var(--red-glow), 0 1px 3px rgba(0,0,0,0.08);
        }
        .message-row.other .bubble {
            background: var(--bubble-in); color: var(--text-main);
            border-radius: 20px 20px 20px 5px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .bubble:hover { filter: brightness(1.02); }

        /* Image in bubble */
        .bubble img.msg-image {
            display: block; max-width: 230px; max-height: 210px;
            border-radius: 12px; object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            cursor: pointer; transition: opacity 0.15s;
        }
        .bubble img.msg-image:hover { opacity: 0.92; }
        /* image with caption: add spacing above image */
        .bubble .msg-caption + img.msg-image,
        .bubble img.msg-image + .msg-caption { margin-top: 6px; }
        /* image-only: no padding around bubble */
        .bubble.image-only { padding: 6px; }
        .bubble.image-only img.msg-image { margin-top: 0; border-radius: 14px; }

        .msg-meta { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
        .message-row.user  .msg-meta { justify-content: flex-end; }
        .message-row.other .msg-meta { justify-content: flex-start; }
        .message-time { font-size: 10px; color: var(--text-muted); letter-spacing: 0.2px; }
        .read-receipt { font-size: 10px; color: var(--text-muted); display: flex; align-items: center; gap: 2px; }
        .read-receipt.seen { color: var(--red); }
        .read-receipt.sent { color: #c4bfb8; }

        .sender-avatar {
            width: 30px; height: 30px; border-radius: 10px;
            background: linear-gradient(140deg, #e8796e 0%, var(--red) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: #fff; flex-shrink: 0;
            margin-bottom: 2px; box-shadow: 0 2px 6px var(--red-glow);
        }

        /* ── Image preview bar (above input) ── */
        .image-preview-bar {
            background: #fff; border-top: 1px solid var(--border);
            padding: 10px 16px; display: none; align-items: center; gap: 10px;
            flex-shrink: 0;
        }
        .image-preview-bar.visible { display: flex; }
        .preview-thumb-wrap { position: relative; flex-shrink: 0; }
        .preview-thumb {
            width: 56px; height: 56px; border-radius: 10px;
            object-fit: cover; border: 1.5px solid var(--border);
            display: block;
        }
        .preview-remove {
            position: absolute; top: -6px; right: -6px;
            width: 18px; height: 18px; border-radius: 50%;
            background: #333; color: #fff; border: 2px solid #fff;
            font-size: 9px; font-weight: 700; line-height: 1;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background 0.15s;
        }
        .preview-remove:hover { background: var(--red); }
        .preview-caption-input {
            flex: 1; border: 1.5px solid var(--border); border-radius: 10px;
            padding: 8px 12px; font-size: 13px;
            font-family: 'DM Sans', sans-serif; color: var(--text-main);
            background: var(--bg-page); outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .preview-caption-input:focus {
            border-color: var(--red); background: #fff;
            box-shadow: 0 0 0 3px var(--red-glow);
        }
        .preview-caption-input::placeholder { color: #c0bbb4; }
        .preview-send-btn {
            width: 38px; height: 38px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #d63a29 0%, var(--red) 100%);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 3px 10px var(--red-glow);
            transition: box-shadow 0.2s, transform 0.15s;
        }
        .preview-send-btn:hover { box-shadow: 0 5px 16px var(--red-glow); transform: translateY(-1px); }
        .preview-send-btn:active { transform: scale(0.92); }
        .preview-send-btn svg { width: 15px; height: 15px; fill: #fff; margin-left: 1px; }
        .preview-send-btn.sending { pointer-events: none; }
        .preview-send-btn.sending svg { display: none; }
        .preview-send-btn.sending::after {
            content: ''; width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        .upload-progress {
            font-size: 11px; color: var(--text-muted);
            white-space: nowrap; flex-shrink: 0;
        }

        /* Input footer */
        .input-footer {
            background: #fff; border-top: 1px solid var(--border);
            padding: 12px 16px; display: flex; align-items: center;
            gap: 10px; flex-shrink: 0;
        }
        .input-wrapper {
            flex: 1; background: var(--bg-page); border-radius: 16px;
            display: flex; align-items: center;
            padding: 0 6px 0 14px; gap: 6px;
            border: 1.5px solid transparent;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .input-wrapper:focus-within {
            border-color: var(--red); background: #fff;
            box-shadow: 0 0 0 3px rgba(194,43,27,0.08);
        }
        .input-footer input[type="text"] {
            flex: 1; background: transparent; border: none; outline: none;
            padding: 11px 0; font-size: 14px; color: var(--text-main);
            font-family: 'DM Sans', sans-serif; width: 0;
        }
        .input-footer input[type="text"]::placeholder { color: #c0bbb4; }

        /* Hidden file input */
        #fileInput { display: none; }

        .attach-btn {
            background: none; border: none; cursor: pointer; color: #c0bbb4;
            font-size: 16px; padding: 6px; margin: 0;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; transition: color 0.18s, background 0.18s;
        }
        .attach-btn:hover { color: var(--text-muted); background: var(--bg-page); }
        .attach-btn.has-image { color: var(--red); }

        .send-btn {
            width: 40px; height: 40px; border-radius: 13px; border: none;
            background: linear-gradient(135deg, #d63a29 0%, var(--red) 100%);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; padding: 0; margin: 0;
            box-shadow: 0 3px 10px var(--red-glow);
            transition: box-shadow 0.2s, transform 0.15s, opacity 0.2s;
        }
        .send-btn:hover  { box-shadow: 0 5px 16px var(--red-glow); transform: translateY(-1px); }
        .send-btn:active { transform: scale(0.92); box-shadow: none; }
        .send-btn:disabled { background: #e0dbd4; cursor: not-allowed; box-shadow: none; transform: none; }
        .send-btn svg { width: 16px; height: 16px; fill: #fff; margin-left: 2px; }
        .send-btn.sending { pointer-events: none; }
        .send-btn.sending svg { display: none; }
        .send-btn.sending::after {
            content: ''; width: 15px; height: 15px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Lightbox */
        .lightbox {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.82); align-items: center; justify-content: center;
            animation: lbIn 0.18s ease;
        }
        .lightbox.open { display: flex; }
        @keyframes lbIn { from { opacity: 0; } to { opacity: 1; } }
        .lightbox img {
            max-width: 90vw; max-height: 88vh;
            border-radius: 14px; object-fit: contain;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            animation: lbScale 0.2s cubic-bezier(0.34,1.2,0.64,1);
        }
        @keyframes lbScale { from { transform: scale(0.9); } to { transform: scale(1); } }
        .lightbox-close {
            position: absolute; top: 20px; right: 24px;
            width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.12);
            color: #fff; font-size: 18px; display: flex; align-items: center;
            justify-content: center; cursor: pointer; border: none;
            transition: background 0.15s;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.22); }

        @media (max-width: 900px) {
            .messenger-container { padding: 10px; }
            .bubble { max-width: 80%; }
        }
    </style>
</head>
<body class="dashboard-page">
<div class="dashboard">

    <?php include 'sidebar.php'; ?>

    <main class="main">
        <section class="messenger-container">
            <div class="messenger-card">

                <!-- Header -->
                <div class="chat-header">
                    <a class="back-icon" href="javascript:history.back()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                    </a>
                    <div class="header-avatar-wrap">
                        <div class="header-avatar">MN</div>
                        <span class="online-ring"></span>
                    </div>
                    <div class="header-info">
                        <h2>Mommy Narlyn Printing Shop</h2>
                        <div class="subtitle"><span class="active-dot"></span>Active now</div>
                    </div>
                    <div class="header-actions">
                        <button class="header-action-btn" title="Call">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.63 19.79 19.79 0 013 1.18 2 2 0 015 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L9.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                        </button>
                        <button class="header-action-btn" title="Info">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($dbMessages)): ?>
                        <div class="empty-chat" id="emptyState">
                            <div class="empty-chat-icon">&#128172;</div>
                            <h3>No messages yet</h3>
                            <p>Start the conversation &mdash; we&rsquo;re here to help!</p>
                        </div>
                    <?php else: ?>
                        <?php
                        $lastDate = null;
                        foreach ($dbMessages as $msg):
                            $ts = strtotime($msg['created_at']);
                            if (date('Y-m-d', $ts) === date('Y-m-d')) $dateLabel = 'Today';
                            elseif (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) $dateLabel = 'Yesterday';
                            else $dateLabel = date('M j', $ts);
                            if ($dateLabel !== $lastDate): $lastDate = $dateLabel; ?>
                                <div class="date-separator"><span><?= htmlspecialchars($dateLabel) ?></span></div>
                            <?php endif;
                            $isUser   = ((int)$msg['sender_id'] === $currentUserId);
                            $rowClass = $isUser ? 'user' : 'other';
                            $hasImage = !empty($msg['image_url']);
                            $hasText  = !empty($msg['message_text']);
                            $imageOnly = $hasImage && !$hasText;
                        ?>
                        <div class="message-row <?= $rowClass ?>" data-id="<?= (int)$msg['message_id'] ?>">
                            <?php if (!$isUser): ?><div class="sender-avatar">MN</div><?php endif; ?>
                            <div>
                                <div class="bubble<?= $imageOnly ? ' image-only' : '' ?>">
                                    <?php if ($hasText): ?>
                                        <span class="msg-caption"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($hasImage): ?>
                                        <img class="msg-image" src="<?= htmlspecialchars($msg['image_url']) ?>" alt="Image" onclick="openLightbox(this.src)">
                                    <?php endif; ?>
                                </div>
                                <div class="msg-meta">
                                    <span class="message-time"><?= date('h:i A', $ts) ?></span>
                                    <?php if ($isUser): ?>
                                        <?php if ($msg['is_read']): ?>
                                            <span class="read-receipt seen"><svg width="14" height="10" viewBox="0 0 18 12" fill="none"><path d="M1 6l4 4L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <?php else: ?>
                                            <span class="read-receipt sent"><svg width="12" height="10" viewBox="0 0 14 12" fill="none"><path d="M1 6l4 4L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Image preview bar -->
                <div class="image-preview-bar" id="imagePreviewBar">
                    <div class="preview-thumb-wrap">
                        <img class="preview-thumb" id="previewThumb" src="" alt="Preview">
                        <span class="preview-remove" id="previewRemove" title="Remove">✕</span>
                    </div>
                    <input type="text" class="preview-caption-input" id="captionInput" placeholder="Add a caption… (optional)">
                    <span class="upload-progress" id="uploadProgress"></span>
                    <button class="preview-send-btn" id="previewSendBtn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>

                <!-- Input footer -->
                <div class="input-footer">
                    <div class="input-wrapper">
                        <button class="attach-btn" id="attachBtn" title="Attach image">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                        </button>
                        <input type="text" id="messageInput" placeholder="Write a message…" autocomplete="off">
                        <input type="file" id="fileInput" accept="image/*">
                    </div>
                    <button class="send-btn" id="sendBtn" disabled>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>

            </div>
        </section>
    </main>

</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <img id="lightboxImg" src="" alt="Full image">
</div>

<script>
const CURRENT_USER_ID = <?= $currentUserId ?>;
const chatEl    = document.getElementById('chatMessages');
const inputEl   = document.getElementById('messageInput');
const sendBtn   = document.getElementById('sendBtn');
const attachBtn = document.getElementById('attachBtn');
const fileInput = document.getElementById('fileInput');

// Image preview bar elements
const previewBar     = document.getElementById('imagePreviewBar');
const previewThumb   = document.getElementById('previewThumb');
const previewRemove  = document.getElementById('previewRemove');
const captionInput   = document.getElementById('captionInput');
const previewSendBtn = document.getElementById('previewSendBtn');
const uploadProgress = document.getElementById('uploadProgress');

let lastMessageId = <?= $lastMessageId ?>;
let pendingImageUrl = null; // uploaded image URL waiting to be sent

// ── Lightbox ────────────────────────────────────────────────
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

// ── Helpers ─────────────────────────────────────────────────
function formatTime(iso) {
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
function dateLabel(iso) {
    const d = new Date(iso.replace(' ', 'T'));
    const today = new Date();
    if (d.toDateString() === today.toDateString()) return 'Today';
    const yest = new Date(); yest.setDate(today.getDate() - 1);
    if (d.toDateString() === yest.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
}
function makeDateSep(label) {
    const div = document.createElement('div');
    div.className = 'date-separator';
    div.innerHTML = `<span>${label}</span>`;
    return div;
}

const TICK_SEEN = `<svg width="14" height="10" viewBox="0 0 18 12" fill="none"><path d="M1 6l4 4L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
const TICK_SENT = `<svg width="12" height="10" viewBox="0 0 14 12" fill="none"><path d="M1 6l4 4L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

function makeRow(msg) {
    const isUser = (parseInt(msg.sender_id) === CURRENT_USER_ID);
    const row    = document.createElement('div');
    row.className = `message-row ${isUser ? 'user' : 'other'}`;
    row.dataset.id = msg.message_id;

    if (!isUser) {
        const av = document.createElement('div');
        av.className = 'sender-avatar';
        av.textContent = 'MN';
        row.appendChild(av);
    }

    const wrap   = document.createElement('div');
    const bubble = document.createElement('div');
    const hasText  = msg.message_text && msg.message_text.trim() !== '';
    const hasImage = !!msg.image_url;
    bubble.className = 'bubble' + (hasImage && !hasText ? ' image-only' : '');

    if (hasText) {
        const cap = document.createElement('span');
        cap.className = 'msg-caption';
        cap.innerHTML = msg.message_text.replace(/\n/g, '<br>');
        bubble.appendChild(cap);
    }
    if (hasImage) {
        const img = document.createElement('img');
        img.className = 'msg-image';
        img.src = msg.image_url;
        img.alt = 'Image';
        img.onclick = () => openLightbox(img.src);
        bubble.appendChild(img);
    }

    const meta = document.createElement('div');
    meta.className = 'msg-meta';
    const time = document.createElement('span');
    time.className = 'message-time';
    time.textContent = formatTime(msg.created_at);
    meta.appendChild(time);

    if (isUser) {
        const receipt = document.createElement('span');
        const seen = msg.is_read == 1;
        receipt.className = `read-receipt ${seen ? 'seen' : 'sent'}`;
        receipt.innerHTML = seen ? TICK_SEEN : TICK_SENT;
        meta.appendChild(receipt);
    }

    wrap.appendChild(bubble);
    wrap.appendChild(meta);
    row.appendChild(wrap);
    return row;
}

function appendMessages(msgs) {
    if (!msgs.length) return;
    const empty = chatEl.querySelector('.empty-chat');
    if (empty) chatEl.innerHTML = '';
    const isNearBottom = chatEl.scrollHeight - chatEl.scrollTop - chatEl.clientHeight < 80;
    msgs.forEach(msg => {
        if (chatEl.querySelector(`.message-row[data-id="${msg.message_id}"]`)) return;
        const dl = dateLabel(msg.created_at);
        const seps = chatEl.querySelectorAll('.date-separator span');
        const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
        if (dl !== lastSep) chatEl.appendChild(makeDateSep(dl));
        chatEl.appendChild(makeRow(msg));
        lastMessageId = Math.max(lastMessageId, parseInt(msg.message_id));
    });
    if (isNearBottom) chatEl.scrollTop = chatEl.scrollHeight;
}

// ── Polling ──────────────────────────────────────────────────
let pollTimer = null;
async function poll() {
    try {
        const r = await fetch(`?action=poll&after_id=${lastMessageId}`);
        if (r.ok) { const msgs = await r.json(); if (msgs.length) appendMessages(msgs); }
    } catch (e) {}
    finally { pollTimer = setTimeout(poll, 3000); }
}
pollTimer = setTimeout(poll, 3000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) { clearTimeout(pollTimer); }
    else { clearTimeout(pollTimer); pollTimer = setTimeout(poll, 500); }
});

// ── Send text message ────────────────────────────────────────
async function sendMessage() {
    const text = inputEl.value.trim();
    if (!text) return;
    inputEl.value = '';
    sendBtn.disabled = true;
    sendBtn.classList.add('sending');

    const empty = chatEl.querySelector('.empty-chat');
    if (empty) chatEl.innerHTML = '';

    const seps = chatEl.querySelectorAll('.date-separator span');
    const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
    if (lastSep !== 'Today') chatEl.appendChild(makeDateSep('Today'));

    const now     = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const tempRow = makeRow({ message_text: text, image_url: null, created_at: now, is_read: 0, sender_id: CURRENT_USER_ID, message_id: 'temp' });
    chatEl.appendChild(tempRow);
    chatEl.scrollTop = chatEl.scrollHeight;

    try {
        const fd = new FormData();
        fd.append('message_text', text);
        const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            chatEl.replaceChild(makeRow(data.message), tempRow);
            lastMessageId = Math.max(lastMessageId, parseInt(data.message.message_id));
        } else {
            tempRow.querySelector('.bubble').style.opacity = '0.5';
        }
    } catch (err) { console.error(err); }
    finally { sendBtn.classList.remove('sending'); inputEl.focus(); }
}

inputEl.addEventListener('input', function () { sendBtn.disabled = !this.value.trim(); });
sendBtn.addEventListener('click', sendMessage);
inputEl.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// ── Attach button: open file picker ─────────────────────────
attachBtn.addEventListener('click', () => fileInput.click());

// ── File selected: upload immediately, show preview bar ─────
fileInput.addEventListener('change', async function () {
    const file = this.files[0];
    if (!file) return;
    this.value = ''; // reset so same file can be re-selected

    // Show preview immediately using local object URL
    const objectUrl = URL.createObjectURL(file);
    previewThumb.src = objectUrl;
    previewBar.classList.add('visible');
    captionInput.value = '';
    captionInput.focus();
    attachBtn.classList.add('has-image');
    previewSendBtn.disabled = true;
    uploadProgress.textContent = 'Uploading…';
    pendingImageUrl = null;

    // Upload to server
    try {
        const fd = new FormData();
        fd.append('image', file);
        const resp = await fetch('?action=upload_image', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            pendingImageUrl = data.url;
            previewSendBtn.disabled = false;
            uploadProgress.textContent = '';
        } else {
            uploadProgress.textContent = data.error || 'Upload failed.';
            previewSendBtn.disabled = true;
        }
    } catch (e) {
        uploadProgress.textContent = 'Upload error.';
    }
});

// ── Remove preview ───────────────────────────────────────────
previewRemove.addEventListener('click', clearImagePreview);
function clearImagePreview() {
    previewBar.classList.remove('visible');
    previewThumb.src = '';
    captionInput.value = '';
    pendingImageUrl = null;
    attachBtn.classList.remove('has-image');
    uploadProgress.textContent = '';
    previewSendBtn.disabled = false;
}

// ── Send image (with optional caption) ──────────────────────
previewSendBtn.addEventListener('click', sendImage);
captionInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendImage(); }
});

async function sendImage() {
    if (!pendingImageUrl) return;

    // Capture values BEFORE clearing the preview UI
    const capturedUrl     = pendingImageUrl;
    const capturedCaption = captionInput.value.trim();

    previewSendBtn.classList.add('sending');
    previewSendBtn.disabled = true;

    const empty = chatEl.querySelector('.empty-chat');
    if (empty) chatEl.innerHTML = '';

    const seps = chatEl.querySelectorAll('.date-separator span');
    const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
    if (lastSep !== 'Today') chatEl.appendChild(makeDateSep('Today'));

    // Optimistic bubble
    const now     = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const tempRow = makeRow({ message_text: capturedCaption, image_url: capturedUrl, created_at: now, is_read: 0, sender_id: CURRENT_USER_ID, message_id: 'temp_img' });
    chatEl.appendChild(tempRow);
    chatEl.scrollTop = chatEl.scrollHeight;

    // Clear preview bar
    clearImagePreview();

    try {
        const fd = new FormData();
        fd.append('message_text', capturedCaption);
        fd.append('image_url', capturedUrl);
        const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            const existing = chatEl.querySelector('.message-row[data-id="temp_img"]');
            if (existing) chatEl.replaceChild(makeRow(data.message), existing);
            lastMessageId = Math.max(lastMessageId, parseInt(data.message.message_id));
        } else {
            const b = chatEl.querySelector('.message-row[data-id="temp_img"] .bubble');
            if (b) b.style.opacity = '0.5';
        }
    } catch (err) { console.error(err); }
    finally { previewSendBtn.classList.remove('sending'); }
}

chatEl.scrollTop = chatEl.scrollHeight;
</script>
</body>
</html>