<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db.php';
$SHOP_ID = 1;

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

// ── AJAX: get messages for a specific user ────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    header('Content-Type: application/json');
    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) { echo json_encode([]); exit(); }

    $stmt = mysqli_prepare($conn, "
        SELECT m.`message_id`, m.`sender_id`, m.`receiver_id`,
               m.`message_text`, m.`is_read`, m.`created_at`,
               m.`updated_at`, m.`image_url`
        FROM `messages` m
        WHERE (m.`sender_id` = ? AND m.`receiver_id` = ?)
           OR (m.`sender_id` = ? AND m.`receiver_id` = ?)
        ORDER BY m.`created_at` ASC
    ");
    mysqli_stmt_bind_param($stmt, 'iiii', $userId, $SHOP_ID, $SHOP_ID, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $msgs   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    $mark = mysqli_prepare($conn, "
        UPDATE `messages` SET `is_read` = 1, `updated_at` = NOW()
        WHERE `sender_id` = ? AND `receiver_id` = ? AND `is_read` = 0
    ");
    mysqli_stmt_bind_param($mark, 'ii', $userId, $SHOP_ID);
    mysqli_stmt_execute($mark);
    mysqli_stmt_close($mark);

    echo json_encode($msgs);
    exit();
}

// ── AJAX: poll new messages in active chat ────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'poll') {
    header('Content-Type: application/json');
    $userId  = (int)($_GET['user_id']  ?? 0);
    $afterId = (int)($_GET['after_id'] ?? 0);
    if (!$userId) { echo json_encode([]); exit(); }

    $stmt = mysqli_prepare($conn, "
        SELECT `message_id`, `sender_id`, `receiver_id`, `message_text`,
               `is_read`, `created_at`, `updated_at`, `image_url`
        FROM `messages`
        WHERE ((`sender_id` = ? AND `receiver_id` = ?)
            OR (`sender_id` = ? AND `receiver_id` = ?))
          AND `message_id` > ?
        ORDER BY `created_at` ASC
    ");
    mysqli_stmt_bind_param($stmt, 'iiiii', $userId, $SHOP_ID, $SHOP_ID, $userId, $afterId);
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result($stmt);
    $newMsgs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    if (!empty($newMsgs)) {
        $mark = mysqli_prepare($conn, "
            UPDATE `messages` SET `is_read` = 1, `updated_at` = NOW()
            WHERE `sender_id` = ? AND `receiver_id` = ? AND `is_read` = 0
        ");
        mysqli_stmt_bind_param($mark, 'ii', $userId, $SHOP_ID);
        mysqli_stmt_execute($mark);
        mysqli_stmt_close($mark);
    }

    echo json_encode($newMsgs);
    exit();
}

// ── AJAX: poll sidebar ────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'poll_sidebar') {
    header('Content-Type: application/json');

    $stmt = mysqli_prepare($conn, "
        SELECT
            u.`id`                        AS user_id,
            lm.`message_text`             AS last_message,
            lm.`image_url`                AS last_image_url,
            lm.`created_at`               AS last_at,
            lm.`sender_id`                AS last_sender,
            (
                SELECT COUNT(*) FROM `messages` uc
                WHERE uc.`sender_id` = u.`id`
                  AND uc.`receiver_id` = ?
                  AND uc.`is_read` = 0
            ) AS unread_count
        FROM `users` u
        INNER JOIN `messages` lm
            ON lm.`message_id` = (
                SELECT `message_id` FROM `messages`
                WHERE (`sender_id` = u.`id` AND `receiver_id` = ?)
                   OR (`sender_id` = ?       AND `receiver_id` = u.`id`)
                ORDER BY `created_at` DESC LIMIT 1
            )
        WHERE u.`id` != ?
        ORDER BY lm.`created_at` DESC
    ");
    mysqli_stmt_bind_param($stmt, 'iiii', $SHOP_ID, $SHOP_ID, $SHOP_ID, $SHOP_ID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    echo json_encode($rows);
    exit();
}

// ── AJAX: send reply as shop ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $userId   = (int)($_POST['user_id']     ?? 0);
    $text     = trim($_POST['message_text'] ?? '');
    $imageUrl = trim($_POST['image_url']    ?? '') ?: null;

    if (!$userId || ($text === '' && $imageUrl === null)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit();
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO `messages` (`sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`, `updated_at`, `image_url`)
        VALUES (?, ?, ?, 0, NOW(), NOW(), ?)
    ");
    mysqli_stmt_bind_param($stmt, 'iiss', $SHOP_ID, $userId, $text, $imageUrl);
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

// ── Page load: fetch conversation list ───────────────────────
$convStmt = mysqli_prepare($conn, "
    SELECT
        u.`id`                          AS user_id,
        u.`first_name`, u.`last_name`, u.`email`,
        lm.`message_text`               AS last_message,
        lm.`image_url`                  AS last_image_url,
        lm.`created_at`                 AS last_at,
        lm.`sender_id`                  AS last_sender,
        (
            SELECT COUNT(*) FROM `messages` uc
            WHERE uc.`sender_id`   = u.`id`
              AND uc.`receiver_id` = ?
              AND uc.`is_read`     = 0
        ) AS unread_count
    FROM `users` u
    INNER JOIN `messages` lm
        ON lm.`message_id` = (
            SELECT `message_id` FROM `messages`
            WHERE (`sender_id` = u.`id`   AND `receiver_id` = ?)
               OR (`sender_id` = ?         AND `receiver_id` = u.`id`)
            ORDER BY `created_at` DESC LIMIT 1
        )
    WHERE u.`id` != ?
    ORDER BY lm.`created_at` DESC
");
mysqli_stmt_bind_param($convStmt, 'iiii', $SHOP_ID, $SHOP_ID, $SHOP_ID, $SHOP_ID);
mysqli_stmt_execute($convStmt);
$convResult    = mysqli_stmt_get_result($convStmt);
$conversations = mysqli_fetch_all($convResult, MYSQLI_ASSOC);
mysqli_stmt_close($convStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COZIEST Admin – Messages</title>
    <link rel="stylesheet" href="./styles/index.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --red:          #c22b1b;
            --red-dark:     #a82416;
            --red-glow:     rgba(194, 43, 27, 0.16);
            --bg-page:      #f2f0ec;
            --bg-card:      #ffffff;
            --bg-chat:      #f5f3ef;
            --border:       #e8e5df;
            --text-main:    #1c1917;
            --text-muted:   #9b9490;
            --bubble-in:    #ffffff;
            --sidebar-w:    300px;
        }

        body.dashboard-page { font-family: 'DM Sans', 'Segoe UI', sans-serif; }

        .main {
            flex: 1; display: flex; flex-direction: column;
            height: 100vh; overflow: hidden; padding: 0;
            background: var(--bg-page);
        }

        .messenger-container {
            flex: 1; display: flex;
            height: 100vh; padding: 20px; gap: 14px; overflow: hidden;
        }

        /* ── Sidebar ── */
        .conv-sidebar {
            width: var(--sidebar-w); flex-shrink: 0;
            display: flex; flex-direction: column;
            background: var(--bg-card); border-radius: 24px; overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04), 0 10px 32px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }
        .conv-header { padding: 18px 16px 14px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .conv-header h2 { font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0 0 12px; }
        .search-box {
            display: flex; align-items: center;
            background: var(--bg-page); border-radius: 12px;
            padding: 0 10px; gap: 8px;
            border: 1.5px solid transparent;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .search-box:focus-within { border-color: var(--red); background: #fff; box-shadow: 0 0 0 3px var(--red-glow); }
        .search-box svg { color: var(--text-muted); flex-shrink: 0; }
        .search-box input {
            flex: 1; border: none; background: transparent; outline: none;
            padding: 9px 0; font-size: 13px; color: var(--text-main);
            font-family: 'DM Sans', sans-serif;
        }
        .search-box input::placeholder { color: #c0bbb4; }

        .conv-list { flex: 1; overflow-y: auto; }
        .conv-list::-webkit-scrollbar { width: 3px; }
        .conv-list::-webkit-scrollbar-thumb { background: #d8d4cd; border-radius: 99px; }

        .conv-item {
            display: flex; align-items: center; gap: 11px;
            padding: 11px 14px; cursor: pointer;
            border-bottom: 1px solid #f0ede8;
            transition: background 0.15s; position: relative;
        }
        .conv-item:hover  { background: #faf9f7; }
        .conv-item.active { background: #fff5f4; }
        .conv-item.active::before {
            content: ''; position: absolute; left: 0; top: 12%; bottom: 12%;
            width: 3px; background: var(--red); border-radius: 0 3px 3px 0;
        }

        .conv-avatar {
            width: 40px; height: 40px; border-radius: 14px;
            background: linear-gradient(140deg, #e8796e 0%, var(--red) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            flex-shrink: 0; position: relative;
            box-shadow: 0 2px 8px var(--red-glow);
        }
        .unread-badge {
            position: absolute; top: -3px; right: -3px;
            min-width: 16px; height: 16px; padding: 0 3px;
            background: var(--red); border-radius: 8px;
            font-size: 9px; font-weight: 700; color: #fff;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #fff;
            animation: badgeIn 0.2s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes badgeIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .conv-info { flex: 1; min-width: 0; }
        .conv-name { font-size: 13px; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-preview { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
        .conv-preview.unread { color: var(--text-main); font-weight: 600; }
        .conv-meta { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; gap: 4px; }
        .conv-time { font-size: 10px; color: #c0bbb4; white-space: nowrap; }
        .conv-item.active .conv-time { color: var(--red); }
        .empty-conv { padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 13px; }

        /* ── Chat panel ── */
        .chat-panel {
            flex: 1; display: flex; flex-direction: column;
            background: var(--bg-card); border-radius: 24px; overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04), 0 10px 32px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }

        .no-chat-selected {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 14px;
            background: var(--bg-chat);
        }
        .no-chat-icon {
            width: 72px; height: 72px; border-radius: 22px;
            background: linear-gradient(135deg, #f0ede8, #e4ddd5);
            display: flex; align-items: center; justify-content: center; font-size: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        }
        .no-chat-selected h3 { font-size: 15px; font-weight: 700; color: #7a746d; margin: 0; }
        .no-chat-selected p  { font-size: 13px; color: var(--text-muted); margin: 0; }

        .chat-header {
            padding: 13px 20px; background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
            flex-shrink: 0; position: relative;
        }
        .chat-header::after {
            content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--red) 0%, transparent 55%); opacity: 0.3;
        }
        .chat-header-avatar {
            width: 38px; height: 38px; border-radius: 13px;
            background: linear-gradient(140deg, #e8796e 0%, var(--red) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0;
            box-shadow: 0 3px 10px var(--red-glow);
        }
        .chat-header-info { flex: 1; min-width: 0; }
        .chat-header-info h3 { font-size: 14px; font-weight: 700; color: var(--text-main); margin: 0 0 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-header-info p  { font-size: 11px; color: var(--text-muted); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .chat-messages {
            flex: 1; overflow-y: auto; padding: 22px 20px 10px;
            background: var(--bg-chat);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
            display: flex; flex-direction: column; gap: 4px; scroll-behavior: smooth;
        }
        .chat-messages::-webkit-scrollbar { width: 5px; }
        .chat-messages::-webkit-scrollbar-thumb { background: #d8d4cd; border-radius: 99px; }
        .chat-messages::-webkit-scrollbar-thumb:hover { background: #c0bab2; }

        .date-separator { display: flex; align-items: center; gap: 10px; margin: 14px 0 10px; }
        .date-separator::before, .date-separator::after { content: ''; flex: 1; height: 1px; background: #dedad4; }
        .date-separator span {
            font-size: 10.5px; color: #b0aa9f; font-weight: 600;
            letter-spacing: 0.5px; text-transform: uppercase;
            white-space: nowrap; padding: 0 6px; background: var(--bg-chat);
        }

        .msg-loading { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 20px; color: var(--text-muted); font-size: 13px; }

        .message-row {
            display: flex; width: 100%; align-items: flex-end; gap: 8px;
            animation: msgIn 0.22s cubic-bezier(0.34, 1.2, 0.64, 1) both;
        }
        @keyframes msgIn { from { opacity: 0; transform: translateY(8px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .message-row.shop   { justify-content: flex-end; }
        .message-row.client { justify-content: flex-start; }
        .message-row + .message-row.shop,
        .message-row + .message-row.client  { margin-top: 2px; }
        .message-row.shop   + .message-row.client,
        .message-row.client + .message-row.shop  { margin-top: 10px; }

        .bubble { max-width: 100%; padding: 10px 14px; font-size: 14px; line-height: 1.55; word-wrap: break-word; }
        .message-row.shop .bubble {
            background: linear-gradient(135deg, #d63a29 0%, var(--red) 100%);
            color: #fff; border-radius: 20px 20px 5px 20px;
            box-shadow: 0 3px 14px var(--red-glow), 0 1px 3px rgba(0,0,0,0.07);
        }
        .message-row.client .bubble {
            background: var(--bubble-in); color: var(--text-main);
            border-radius: 20px 20px 20px 5px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .bubble:hover { filter: brightness(1.02); }

        .bubble img.msg-image {
            display: block; max-width: 210px; max-height: 190px;
            border-radius: 12px; object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            cursor: pointer; transition: opacity 0.15s;
        }
        .bubble img.msg-image:hover { opacity: 0.92; }
        .bubble .msg-caption + img.msg-image { margin-top: 6px; }
        .bubble.image-only { padding: 6px; }
        .bubble.image-only img.msg-image { margin-top: 0; border-radius: 14px; }

        .msg-meta { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
        .message-row.shop   .msg-meta { justify-content: flex-end; }
        .message-row.client .msg-meta { justify-content: flex-start; }
        .message-time { font-size: 10px; color: var(--text-muted); }
        .read-receipt { font-size: 10px; display: flex; align-items: center; gap: 2px; }
        .read-receipt.seen { color: var(--red); }
        .read-receipt.sent { color: #c4bfb8; }

        .msg-sender-avatar {
            width: 30px; height: 30px; border-radius: 10px;
            background: linear-gradient(140deg, #e8796e 0%, var(--red) 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: #fff; flex-shrink: 0;
            margin-bottom: 2px; box-shadow: 0 2px 6px var(--red-glow);
        }

        /* ── Image preview bar ── */
        .image-preview-bar {
            background: #fff; border-top: 1px solid var(--border);
            padding: 10px 16px; display: none; align-items: center; gap: 10px;
            flex-shrink: 0;
        }
        .image-preview-bar.visible { display: flex; }
        .preview-thumb-wrap { position: relative; flex-shrink: 0; }
        .preview-thumb { width: 56px; height: 56px; border-radius: 10px; object-fit: cover; border: 1.5px solid var(--border); display: block; }
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
        .preview-caption-input:focus { border-color: var(--red); background: #fff; box-shadow: 0 0 0 3px var(--red-glow); }
        .preview-caption-input::placeholder { color: #c0bbb4; }
        .preview-send-btn {
            width: 38px; height: 38px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #d63a29 0%, var(--red) 100%);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 3px 10px var(--red-glow);
            transition: box-shadow 0.2s, transform 0.15s;
        }
        .preview-send-btn:hover  { box-shadow: 0 5px 16px var(--red-glow); transform: translateY(-1px); }
        .preview-send-btn:active { transform: scale(0.92); }
        .preview-send-btn svg { width: 15px; height: 15px; fill: #fff; margin-left: 1px; }
        .preview-send-btn.sending { pointer-events: none; }
        .preview-send-btn.sending svg { display: none; }
        .preview-send-btn.sending::after {
            content: ''; width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff;
            border-radius: 50%; animation: spin 0.6s linear infinite;
        }
        .upload-progress { font-size: 11px; color: var(--text-muted); white-space: nowrap; flex-shrink: 0; }

        /* Input footer */
        .input-footer {
            background: var(--bg-card); border-top: 1px solid var(--border);
            padding: 12px 16px; display: flex; align-items: center; gap: 10px; flex-shrink: 0;
        }
        .input-wrapper {
            flex: 1; background: var(--bg-page); border-radius: 16px;
            display: flex; align-items: center; padding: 0 6px 0 14px; gap: 6px;
            border: 1.5px solid transparent;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .input-wrapper:focus-within { border-color: var(--red); background: #fff; box-shadow: 0 0 0 3px var(--red-glow); }
        .input-footer input[type="text"] {
            flex: 1; background: transparent; border: none; outline: none;
            padding: 11px 0; font-size: 14px; color: var(--text-main);
            font-family: 'DM Sans', sans-serif; width: 0;
        }
        .input-footer input[type="text"]::placeholder { color: #c0bbb4; }
        .input-footer input[type="text"]:disabled { cursor: not-allowed; opacity: 0.5; }
        #fileInput { display: none; }

        .attach-btn {
            background: none; border: none; cursor: pointer; color: #c0bbb4;
            font-size: 16px; padding: 6px; margin: 0;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; transition: color 0.18s, background 0.18s;
        }
        .attach-btn:hover { color: var(--text-muted); background: var(--bg-page); }
        .attach-btn.has-image { color: var(--red); }
        .attach-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        .send-btn {
            width: 40px; height: 40px; border-radius: 13px; border: none;
            background: linear-gradient(135deg, #d63a29 0%, var(--red) 100%);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; padding: 0; margin: 0; box-shadow: 0 3px 10px var(--red-glow);
            transition: box-shadow 0.2s, transform 0.15s;
        }
        .send-btn:hover  { box-shadow: 0 5px 16px var(--red-glow); transform: translateY(-1px); }
        .send-btn:active { transform: scale(0.92); box-shadow: none; }
        .send-btn:disabled { background: #e0dbd4; cursor: not-allowed; box-shadow: none; transform: none; }
        .send-btn svg { width: 16px; height: 16px; fill: #fff; margin-left: 2px; }
        .send-btn.sending { pointer-events: none; }
        .send-btn.sending svg { display: none; }
        .send-btn.sending::after {
            content: ''; width: 15px; height: 15px;
            border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff;
            border-radius: 50%; animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Lightbox */
        .lightbox {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.82); align-items: center; justify-content: center;
        }
        .lightbox.open { display: flex; animation: lbIn 0.18s ease; }
        @keyframes lbIn { from { opacity: 0; } to { opacity: 1; } }
        .lightbox img {
            max-width: 90vw; max-height: 88vh; border-radius: 14px; object-fit: contain;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            animation: lbScale 0.2s cubic-bezier(0.34,1.2,0.64,1);
        }
        @keyframes lbScale { from { transform: scale(0.9); } to { transform: scale(1); } }
        .lightbox-close {
            position: absolute; top: 20px; right: 24px;
            width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.12);
            color: #fff; font-size: 18px; display: flex; align-items: center;
            justify-content: center; cursor: pointer; border: none; transition: background 0.15s;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.22); }

        @media (max-width: 900px) { :root { --sidebar-w: 240px; } }
        @media (max-width: 640px) {
            .messenger-container { padding: 10px; gap: 10px; }
            :root { --sidebar-w: 200px; }
        }
    </style>
</head>
<body class="dashboard-page">
<div class="dashboard">

    <?php include 'sidebar.php'; ?>

    <main class="main">
        <section class="messenger-container">

            <!-- Sidebar -->
            <div class="conv-sidebar">
                <div class="conv-header">
                    <h2>Messages</h2>
                    <div class="search-box">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="searchInput" placeholder="Search customers…">
                    </div>
                </div>

                <div class="conv-list" id="convList">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-conv">No conversations yet.</div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv):
                            $initials   = strtoupper(substr($conv['first_name'], 0, 1) . substr($conv['last_name'], 0, 1));
                            $fullName   = htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']);
                            $unread     = (int)$conv['unread_count'];
                            $lastSender = (int)$conv['last_sender'];
                            $isShopMsg  = ($lastSender === $SHOP_ID);
                            // Preview: show 📷 Photo if last message was image-only
                            $previewText = '';
                            if (!empty($conv['last_message'])) {
                                $previewText = htmlspecialchars(mb_substr($conv['last_message'], 0, 30)) . (mb_strlen($conv['last_message']) > 30 ? '…' : '');
                            } elseif (!empty($conv['last_image_url'])) {
                                $previewText = '📷 Photo';
                            }
                            $ts = strtotime($conv['last_at']);
                            if (date('Y-m-d', $ts) === date('Y-m-d')) $timeLabel = date('h:i A', $ts);
                            elseif (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) $timeLabel = 'Yesterday';
                            else $timeLabel = date('M j', $ts);
                        ?>
                        <div class="conv-item"
                             data-user-id="<?= $conv['user_id'] ?>"
                             data-name="<?= $fullName ?>"
                             data-initials="<?= htmlspecialchars($initials) ?>"
                             data-email="<?= htmlspecialchars($conv['email']) ?>">
                            <div class="conv-avatar">
                                <?= htmlspecialchars($initials) ?>
                                <?php if ($unread > 0): ?>
                                    <span class="unread-badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="conv-info">
                                <div class="conv-name"><?= $fullName ?></div>
                                <div class="conv-preview <?= $unread > 0 ? 'unread' : '' ?>">
                                    <?= ($isShopMsg ? 'You: ' : '') . $previewText ?>
                                </div>
                            </div>
                            <div class="conv-meta">
                                <span class="conv-time"><?= htmlspecialchars($timeLabel) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat panel -->
            <div class="chat-panel" id="chatPanel">

                <div class="no-chat-selected" id="noChatSelected">
                    <div class="no-chat-icon">&#128172;</div>
                    <h3>No conversation open</h3>
                    <p>Pick a customer from the list to start replying</p>
                </div>

                <div class="chat-header" id="chatHeader" style="display:none;">
                    <div class="chat-header-avatar" id="chatHeaderAvatar"></div>
                    <div class="chat-header-info">
                        <h3 id="chatHeaderName"></h3>
                        <p id="chatHeaderEmail"></p>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages" style="display:none;"></div>

                <!-- Image preview bar -->
                <div class="image-preview-bar" id="imagePreviewBar" style="display:none;">
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

                <div class="input-footer" id="inputFooter" style="display:none;">
                    <div class="input-wrapper">
                        <button class="attach-btn" id="attachBtn" title="Attach image" disabled>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                        </button>
                        <input type="text" id="messageInput" placeholder="Reply as Mommy Narlyn…" autocomplete="off" disabled>
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
const SHOP_ID = <?= $SHOP_ID ?>;

let activeUserId   = null;
let activeInitials = '';
let lastMessageId  = 0;
let pollTimer      = null;
let sidebarTimer   = null;
let pendingImageUrl = null;

// Elements
const convListEl    = document.getElementById('convList');
const noChatEl      = document.getElementById('noChatSelected');
const chatHeader    = document.getElementById('chatHeader');
const chatAvatarEl  = document.getElementById('chatHeaderAvatar');
const chatNameEl    = document.getElementById('chatHeaderName');
const chatEmailEl   = document.getElementById('chatHeaderEmail');
const chatMsgEl     = document.getElementById('chatMessages');
const inputFooterEl = document.getElementById('inputFooter');
const inputEl       = document.getElementById('messageInput');
const sendBtn       = document.getElementById('sendBtn');
const searchInput   = document.getElementById('searchInput');
const attachBtn     = document.getElementById('attachBtn');
const fileInput     = document.getElementById('fileInput');

// Preview bar elements
const previewBar     = document.getElementById('imagePreviewBar');
const previewThumb   = document.getElementById('previewThumb');
const previewRemove  = document.getElementById('previewRemove');
const captionInput   = document.getElementById('captionInput');
const previewSendBtn = document.getElementById('previewSendBtn');
const uploadProgress = document.getElementById('uploadProgress');

// ── Lightbox ─────────────────────────────────────────────────
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

// ── Helpers ───────────────────────────────────────────────────
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
    const isShop = (parseInt(msg.sender_id) === SHOP_ID);
    const row    = document.createElement('div');
    row.className = `message-row ${isShop ? 'shop' : 'client'}`;
    row.dataset.id = msg.message_id;

    if (!isShop) {
        const av = document.createElement('div');
        av.className = 'msg-sender-avatar';
        av.textContent = activeInitials;
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

    if (isShop) {
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

// ── Load messages for a user ──────────────────────────────────
async function loadChat(userId) {
    chatMsgEl.innerHTML = '<div class="msg-loading">Loading…</div>';
    clearTimeout(pollTimer);
    lastMessageId = 0;

    const resp = await fetch(`?action=get_messages&user_id=${userId}`);
    const msgs = await resp.json();
    chatMsgEl.innerHTML = '';

    if (!msgs.length) {
        chatMsgEl.innerHTML = '<div class="msg-loading">No messages yet.</div>';
    } else {
        let lastDate = null;
        msgs.forEach(m => {
            const dl = dateLabel(m.created_at);
            if (dl !== lastDate) { chatMsgEl.appendChild(makeDateSep(dl)); lastDate = dl; }
            chatMsgEl.appendChild(makeRow(m));
            lastMessageId = Math.max(lastMessageId, parseInt(m.message_id));
        });
        chatMsgEl.scrollTop = chatMsgEl.scrollHeight;
    }

    // Clear badge
    const item = convListEl.querySelector(`.conv-item[data-user-id="${userId}"]`);
    if (item) {
        const badge = item.querySelector('.unread-badge');
        if (badge) badge.remove();
        const prev = item.querySelector('.conv-preview');
        if (prev) prev.classList.remove('unread');
    }

    startPolling(userId);
}

// ── Select conversation ───────────────────────────────────────
function selectConversation(item) {
    document.querySelectorAll('.conv-item.active').forEach(el => el.classList.remove('active'));
    item.classList.add('active');

    activeUserId   = item.dataset.userId;
    activeInitials = item.dataset.initials;

    noChatEl.style.display      = 'none';
    chatHeader.style.display    = 'flex';
    chatMsgEl.style.display     = 'flex';
    inputFooterEl.style.display = 'flex';
    previewBar.style.display    = 'none'; // reset preview on conversation switch
    pendingImageUrl = null;
    attachBtn.classList.remove('has-image');

    chatAvatarEl.textContent = activeInitials;
    chatNameEl.textContent   = item.dataset.name;
    chatEmailEl.textContent  = item.dataset.email;

    inputEl.disabled  = false;
    attachBtn.disabled = false;
    inputEl.placeholder = `Reply to ${item.dataset.name.split(' ')[0]}…`;

    loadChat(activeUserId);
}

convListEl.addEventListener('click', e => {
    const item = e.target.closest('.conv-item');
    if (item) selectConversation(item);
});

// ── Send text message ─────────────────────────────────────────
async function sendMessage() {
    const text = inputEl.value.trim();
    if (!text || !activeUserId) return;

    inputEl.value = '';
    sendBtn.disabled = true;
    sendBtn.classList.add('sending');

    const seps = chatMsgEl.querySelectorAll('.date-separator span');
    const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
    if (lastSep !== 'Today') chatMsgEl.appendChild(makeDateSep('Today'));

    const now     = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const tempRow = makeRow({ message_text: text, image_url: null, created_at: now, is_read: 0, sender_id: SHOP_ID, message_id: 'temp' });
    tempRow.classList.add('optimistic');
    chatMsgEl.appendChild(tempRow);
    chatMsgEl.scrollTop = chatMsgEl.scrollHeight;

    try {
        const fd = new FormData();
        fd.append('user_id', activeUserId);
        fd.append('message_text', text);
        const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            chatMsgEl.replaceChild(makeRow(data.message), tempRow);
            lastMessageId = Math.max(lastMessageId, parseInt(data.message.message_id));
            updateSidebarPreview(activeUserId, data.message);
        } else {
            tempRow.querySelector('.bubble').style.opacity = '0.5';
        }
    } catch (err) { console.error(err); }
    finally { sendBtn.classList.remove('sending'); inputEl.focus(); }
}

inputEl.addEventListener('input', () => { sendBtn.disabled = !inputEl.value.trim(); });
sendBtn.addEventListener('click', sendMessage);
inputEl.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// ── Attach / file input ───────────────────────────────────────
attachBtn.addEventListener('click', () => { if (!attachBtn.disabled) fileInput.click(); });

fileInput.addEventListener('change', async function () {
    const file = this.files[0];
    if (!file) return;
    this.value = '';

    const objectUrl = URL.createObjectURL(file);
    previewThumb.src = objectUrl;

    // Show preview bar — use flex manually (it was hidden with style attr)
    previewBar.style.display = 'flex';
    previewBar.classList.add('visible');
    captionInput.value = '';
    captionInput.focus();
    attachBtn.classList.add('has-image');
    previewSendBtn.disabled = true;
    uploadProgress.textContent = 'Uploading…';
    pendingImageUrl = null;

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
        }
    } catch (e) {
        uploadProgress.textContent = 'Upload error.';
    }
});

previewRemove.addEventListener('click', clearImagePreview);
function clearImagePreview() {
    previewBar.style.display = 'none';
    previewBar.classList.remove('visible');
    previewThumb.src = '';
    captionInput.value = '';
    pendingImageUrl = null;
    attachBtn.classList.remove('has-image');
    uploadProgress.textContent = '';
    previewSendBtn.disabled = false;
}

// ── Send image with optional caption ─────────────────────────
previewSendBtn.addEventListener('click', sendImage);
captionInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendImage(); }
});

async function sendImage() {
    if (!pendingImageUrl || !activeUserId) return;

    // Capture before clearing
    const capturedUrl     = pendingImageUrl;
    const capturedCaption = captionInput.value.trim();

    previewSendBtn.classList.add('sending');
    previewSendBtn.disabled = true;

    const seps = chatMsgEl.querySelectorAll('.date-separator span');
    const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
    if (lastSep !== 'Today') chatMsgEl.appendChild(makeDateSep('Today'));

    const now     = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const tempRow = makeRow({ message_text: capturedCaption, image_url: capturedUrl, created_at: now, is_read: 0, sender_id: SHOP_ID, message_id: 'temp_img' });
    chatMsgEl.appendChild(tempRow);
    chatMsgEl.scrollTop = chatMsgEl.scrollHeight;

    clearImagePreview();

    try {
        const fd = new FormData();
        fd.append('user_id', activeUserId);
        fd.append('message_text', capturedCaption);
        fd.append('image_url', capturedUrl);
        const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const data = await resp.json();
        if (data.success) {
            const existing = chatMsgEl.querySelector('.message-row[data-id="temp_img"]');
            if (existing) chatMsgEl.replaceChild(makeRow(data.message), existing);
            lastMessageId = Math.max(lastMessageId, parseInt(data.message.message_id));
            updateSidebarPreview(activeUserId, data.message);
        } else {
            const b = chatMsgEl.querySelector('.message-row[data-id="temp_img"] .bubble');
            if (b) b.style.opacity = '0.5';
        }
    } catch (err) { console.error(err); }
    finally { previewSendBtn.classList.remove('sending'); }
}

// ── Append polled messages ────────────────────────────────────
function appendNewMessages(msgs) {
    if (!msgs.length) return;
    const placeholder = chatMsgEl.querySelector('.msg-loading');
    if (placeholder) chatMsgEl.innerHTML = '';
    const isNearBottom = chatMsgEl.scrollHeight - chatMsgEl.scrollTop - chatMsgEl.clientHeight < 80;
    msgs.forEach(msg => {
        if (chatMsgEl.querySelector(`.message-row[data-id="${msg.message_id}"]`)) return;
        const dl   = dateLabel(msg.created_at);
        const seps = chatMsgEl.querySelectorAll('.date-separator span');
        const lastSep = seps.length ? seps[seps.length - 1].textContent : null;
        if (dl !== lastSep) chatMsgEl.appendChild(makeDateSep(dl));
        chatMsgEl.appendChild(makeRow(msg));
        lastMessageId = Math.max(lastMessageId, parseInt(msg.message_id));
    });
    if (isNearBottom) chatMsgEl.scrollTop = chatMsgEl.scrollHeight;
}

// ── Sidebar preview update ────────────────────────────────────
function updateSidebarPreview(userId, msg) {
    const item = convListEl.querySelector(`.conv-item[data-user-id="${userId}"]`);
    if (!item) return;
    const isShop   = (parseInt(msg.sender_id) === SHOP_ID);
    const preview  = item.querySelector('.conv-preview');
    const timeEl   = item.querySelector('.conv-time');
    const text     = msg.message_text || (msg.image_url ? '📷 Photo' : '');
    if (preview) preview.textContent = (isShop ? 'You: ' : '') + text.substring(0, 32) + (text.length > 32 ? '…' : '');
    if (timeEl)  timeEl.textContent  = formatTime(msg.created_at);
    convListEl.prepend(item);
}

// ── Polling ───────────────────────────────────────────────────
function startPolling(userId) {
    clearTimeout(pollTimer);
    async function doPoll() {
        if (!activeUserId || activeUserId != userId) return;
        try {
            const r = await fetch(`?action=poll&user_id=${userId}&after_id=${lastMessageId}`);
            if (r.ok) {
                const msgs = await r.json();
                if (msgs.length) {
                    appendNewMessages(msgs);
                    updateSidebarPreview(userId, msgs[msgs.length - 1]);
                }
            }
        } catch (e) {}
        pollTimer = setTimeout(doPoll, 3000);
    }
    pollTimer = setTimeout(doPoll, 3000);
}

async function pollSidebar() {
    try {
        const r = await fetch('?action=poll_sidebar');
        if (!r.ok) return;
        const rows = await r.json();
        rows.forEach(row => {
            const item = convListEl.querySelector(`.conv-item[data-user-id="${row.user_id}"]`);
            if (!item) return;
            const isActive  = (activeUserId == row.user_id);
            const unread    = parseInt(row.unread_count);
            const badge     = item.querySelector('.unread-badge');
            const preview   = item.querySelector('.conv-preview');
            const timeEl    = item.querySelector('.conv-time');
            const isShopMsg = (parseInt(row.last_sender) === SHOP_ID);
            const text      = row.last_message || (row.last_image_url ? '📷 Photo' : '');

            if (preview) {
                preview.textContent = (isShopMsg ? 'You: ' : '') + text.substring(0, 32) + (text.length > 32 ? '…' : '');
                preview.classList.toggle('unread', unread > 0 && !isActive);
            }
            if (timeEl) timeEl.textContent = formatTime(row.last_at);

            if (unread > 0 && !isActive) {
                if (badge) { badge.textContent = unread > 9 ? '9+' : unread; }
                else {
                    const av = item.querySelector('.conv-avatar');
                    if (av) {
                        const b = document.createElement('span');
                        b.className = 'unread-badge';
                        b.textContent = unread > 9 ? '9+' : unread;
                        av.appendChild(b);
                    }
                }
            } else if (badge) { badge.remove(); }

            convListEl.appendChild(item);
        });
    } catch (e) {}
    sidebarTimer = setTimeout(pollSidebar, 5000);
}

sidebarTimer = setTimeout(pollSidebar, 5000);

document.addEventListener('visibilitychange', () => {
    if (document.hidden) { clearTimeout(pollTimer); clearTimeout(sidebarTimer); }
    else {
        clearTimeout(pollTimer); clearTimeout(sidebarTimer);
        if (activeUserId) startPolling(activeUserId);
        sidebarTimer = setTimeout(pollSidebar, 500);
    }
});

// ── Search ────────────────────────────────────────────────────
searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(item => {
        item.style.display = item.dataset.name.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>