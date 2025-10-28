<?php
// print.php - チャンネル詳細 + コメント + 登録者数変更フォーム

// DB接続設定
$dsn = 'mysql:dbname=youtube;host=localhost;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// 必要なテーブルを作成（存在しない場合）

$pdo->exec("CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// チャンネルID取得
$channel_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['channel_id'])) {
    $channel_id = intval($_POST['channel_id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_channel_id'])) {
    $channel_id = intval($_POST['comment_channel_id']);
} elseif (isset($_GET['channel_id'])) {
    $channel_id = intval($_GET['channel_id']);
}

// select.php から来たかどうか
$from_select = false;
if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'select.php') !== false) {
    $from_select = true;
}

if ($from_select && !$channel_id) {
    exit('チャンネルが選択されていません。<a href="select.php">戻る</a>');
}

// --- 登録者数の変更処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_channel_id'], $_POST['new_subscribers'])) {
    $update_id = intval($_POST['update_channel_id']);
    $new_subs = intval($_POST['new_subscribers']);
    if ($update_id > 0 && $new_subs >= 0) {
        $stmt = $pdo->prepare('UPDATE channels SET subscribers = :subs WHERE id = :id');
        $stmt->execute([':subs' => $new_subs, ':id' => $update_id]);
        header('Location: print.php?channel_id=' . $update_id);
        exit;
    }
}

// チャンネル情報取得
$channel = null;
if ($channel_id) {
    $stmt = $pdo->prepare('SELECT c.*, a.name AS affiliation_name FROM channels c LEFT JOIN affiliations a ON c.affiliation_id = a.id WHERE c.id = :id LIMIT 1');
    $stmt->execute([':id' => $channel_id]);
    $channel = $stmt->fetch();
}

// コメント投稿処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $post_channel_id = $channel_id ?: ($_POST['channel_id'] ?? null);
    if ($post_channel_id) {
        $user_name = trim($_POST['name'] ?? '');
        $message = trim($_POST['message']);
        if ($message !== '') {
            $stmt = $pdo->prepare('INSERT INTO comments (channel_id, user_name, message) VALUES (:channel_id, :user_name, :message)');
            $stmt->execute([
                ':channel_id' => $post_channel_id,
                ':user_name' => $user_name,
                ':message' => $message
            ]);
            header('Location: print.php?channel_id=' . $post_channel_id);
            exit;
        }
    }
}

// コメント一覧取得
$comments = [];
if ($channel_id) {
    $stmt = $pdo->prepare('SELECT * FROM comments WHERE channel_id = :cid ORDER BY created_at ASC');
    $stmt->execute([':cid' => $channel_id]);
    $comments = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($channel['name'] ?? 'チャンネル詳細'); ?></title>
<style>
body { font-family: Arial, sans-serif; background:#f9f9f9; margin:40px; }
.container { max-width:800px; margin:0 auto; background:#fff; padding:24px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
h1 { margin-top:0; color:#333; }
.section { margin-bottom:20px; }
label { font-weight:bold; display:block; margin-top:8px; }
input[type=text], input[type=number], textarea { width:100%; padding:8px; margin-top:6px; border:1px solid #ccc; border-radius:6px; }
input[type=submit], button { background:#007bff; color:#fff; border:none; padding:10px 14px; border-radius:6px; cursor:pointer; }
input[type=submit]:hover, button:hover { background:#0056b3; }
.comment { background:#f4f6f8; padding:12px; border-radius:8px; margin-bottom:10px; }
.meta { font-size:0.9em; color:#666; margin-top:6px; }
.back { margin-top:12px; display:inline-block; }
</style>
</head>
<body>
<div class="container">
    <?php if ($channel): ?>
        <h1><?php echo htmlspecialchars($channel['name']); ?></h1>
        <div class="section">
            <p><strong>所属：</strong> <?php echo htmlspecialchars($channel['affiliation_name'] ?? '未所属'); ?></p>
            <p><strong>登録者数：</strong> <?php echo number_format($channel['subscribers']); ?> 人</p>
            <p><strong>最終更新：</strong> <?php echo htmlspecialchars($channel['updated_at'] ?? '不明'); ?></p>
        </div>

<div class="section" style="margin-top: 20px;">
    <div id="subscriberDisplay" style="display: inline-flex; align-items: center; gap: 8px;">
        <span style="font-size: 0.95em; color: #444;">
            登録者数：<?php echo htmlspecialchars($channel['subscribers']); ?>人
        </span>
        <button type="button" 
                onclick="toggleEdit(true)" 
                style="background: none; border: none; color: #007bff; cursor: pointer; font-size: 0.9em;">
            ✏️ 編集
        </button>
    </div>

    <form id="subscriberEditForm" 
          method="POST" 
          action="print.php" 
          style="display: none; align-items: center; gap: 8px; flex-wrap: wrap;">
        <input type="hidden" name="update_channel_id" value="<?php echo htmlspecialchars($channel_id); ?>">
        <label style="font-size: 0.95em; color: #444;">登録者数：</label>
        <input type="number" 
               name="new_subscribers" 
               min="0" 
               value="<?php echo htmlspecialchars($channel['subscribers']); ?>" 
               style="width: 100px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em;"
               required>
        <input type="submit" 
               value="💾 保存" 
               style="background: #0084d7ff; border: 1px solid #ffffffff; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 0.9em;">
        <button type="button" 
                onclick="toggleEdit(false)" 
                style="background: #0084d7ff; border: 1px solid #ffffffff; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 0.9em;">
            ❌ キャンセル
        </button>
    </form>
</div>

<script>
function toggleEdit(isEditing) {
    document.getElementById('subscriberDisplay').style.display = isEditing ? 'none' : 'inline-flex';
    document.getElementById('subscriberEditForm').style.display = isEditing ? 'inline-flex' : 'none';
}
</script>
    <?php else: ?>
        <h1>チャンネル情報</h1>
        <div class="section"><p>特定のチャンネルが選択されていません。<br><a href="select.php">一覧に戻る</a></p></div>
    <?php endif; ?>

    <?php if ($channel): ?>
    <div class="section">
        <h2>コメント投稿</h2>
        <form method="POST" action="print.php">
            <input type="hidden" name="comment_channel_id" value="<?php echo htmlspecialchars($channel_id); ?>">
            <label>名前（任意）</label>
            <input type="text" name="name" placeholder="表示名">
            <label>コメント</label>
            <textarea name="message" rows="4" required></textarea>
            <div style="margin-top:10px;"><input type="submit" value="投稿"></div>
        </form>
    </div>

    <div class="section">
        <h2>コメント一覧</h2>
        <?php if (empty($comments)): ?>
            <p>まだコメントはありません。</p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="comment">
                    <?php echo nl2br(htmlspecialchars($c['message'])); ?>
                    <div class="meta">投稿者: <?php echo htmlspecialchars($c['user_name'] ?? '匿名'); ?>　|　投稿日: <?php echo htmlspecialchars($c['created_at']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <a class="back" href="select.php">← チャンネル一覧に戻る</a>
</div>
</body>
</html>
