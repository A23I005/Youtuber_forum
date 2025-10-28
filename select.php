<?php
// DB接続設定
$host = 'localhost';
$dbname = 'youtube';
$user = 'root';
$pass = '';

try {
    // まず「mysql」DBに接続（どんな環境でも存在する）
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // データベースがなければ作成
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

    // 作成したDBに接続し直す
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 必要なテーブルも存在しなければ自動で作成
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS affiliations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS channels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            subscribers INT DEFAULT 0,
            affiliation_id INT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (affiliation_id) REFERENCES affiliations(id)
        );

        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            channel_id INT NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (channel_id) REFERENCES channels(id)
        );
    ");

} catch (PDOException $e) {
    exit('DB接続または作成エラー: ' . $e->getMessage());
}

// 検索条件取得
$name = $_GET['name'] ?? '';
$affiliation = $_GET['affiliation'] ?? '';
$sort = $_GET['sort'] ?? '';

// SQL構築
$sql = "SELECT c.*, a.name AS affiliation_name FROM channels c 
        LEFT JOIN affiliations a ON c.affiliation_id = a.id";
$conditions = [];
$params = [];

if ($name !== '') {
    $conditions[] = "c.name LIKE ?";
    $params[] = "%{$name}%";
}
if ($affiliation !== '') {
    $conditions[] = "a.name LIKE ?";
    $params[] = "%{$affiliation}%";
}
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// ソート条件を追加
switch ($sort) {
    case 'name_asc':
        $sql .= " ORDER BY c.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY c.name DESC";
        break;
    case 'sub_asc':
        $sql .= " ORDER BY c.subscribers ASC";
        break;
    case 'sub_desc':
        $sql .= " ORDER BY c.subscribers DESC";
        break;
    default:
        $sql .= " ORDER BY c.updated_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$channels = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チャンネル一覧と検索・ソート</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 40px; }
        h1 { color: #333; }
        .search-form, .channel-list { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input[type=text], select { padding: 8px; margin: 5px; border-radius: 5px; border: 1px solid #ccc; }
        input[type=submit] { background-color: #007BFF; color: white; border: none; border-radius: 5px; padding: 8px 16px; cursor: pointer; }
        input[type=submit]:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        form { display: inline; }
        a { text-decoration: none; color: #007BFF; }
    </style>
</head>
<body>

    <h1>🔍 チャンネル検索と並び替え</h1>

    <div class="search-form">
        <form method="GET" action="select.php">
            <label>チャンネル名：</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
            <label>所属：</label>
            <input type="text" name="affiliation" value="<?php echo htmlspecialchars($affiliation); ?>">
            <label>並び替え：</label>
            <select name="sort">
                <option value="" <?php if ($sort=='') echo 'selected'; ?>>更新日（新しい順）</option>
                <option value="name_asc" <?php if ($sort=='name_asc') echo 'selected'; ?>>名前昇順</option>
                <option value="name_desc" <?php if ($sort=='name_desc') echo 'selected'; ?>>名前降順</option>
                <option value="sub_asc" <?php if ($sort=='sub_asc') echo 'selected'; ?>>登録者数昇順</option>
                <option value="sub_desc" <?php if ($sort=='sub_desc') echo 'selected'; ?>>登録者数降順</option>
            </select>
            <input type="submit" value="検索">
            <a href="select.php">リセット</a>
        </form>
    </div>

    <div class="channel-list">
        <h2>📺 チャンネル一覧</h2>
        <?php if (count($channels) === 0): ?>
            <p>該当するチャンネルはありません。</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>チャンネル名</th>
                    <th>所属</th>
                    <th>登録者数</th>
                    <th>更新日</th>
                    <th></th>
                </tr>
                <?php foreach ($channels as $ch): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ch['name']); ?></td>
                    <td><?php echo htmlspecialchars($ch['affiliation_name'] ?? '未所属'); ?></td>
                    <td><?php echo number_format($ch['subscribers']); ?> 人</td>
                    <td><?php echo htmlspecialchars($ch['updated_at']); ?></td>
                    <td>
                        <form method="POST" action="print.php">
                            <input type="hidden" name="channel_id" value="<?php echo htmlspecialchars($ch['id']); ?>">
                            <input type="submit" value="詳細を見る">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <p><a href="initial_screen.php">← トップに戻る</a></p>

</body>
</html>
