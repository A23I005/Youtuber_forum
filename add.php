<?php
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

// 所属テーブル作成
$pdo->exec("CREATE TABLE IF NOT EXISTS affiliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// チャンネルテーブル作成
$pdo->exec("CREATE TABLE IF NOT EXISTS channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    subscribers INT NOT NULL,
    affiliation_id INT,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliation_id) REFERENCES affiliations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$message = '';
$error = '';

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $subscribers = trim($_POST['subscribers'] ?? '');
    $affiliation = trim($_POST['affiliation'] ?? '');

    if ($name === '' || $subscribers === '') {
        $error = 'チャンネル名と登録者数は必須です。';
    } elseif (!is_numeric($subscribers)) {
        $error = '登録者数は数値で入力してください。';
    } else {
        try {
            // 所属の登録 or 取得
            $stmt = $pdo->prepare("SELECT id FROM affiliations WHERE name = ?");
            $stmt->execute([$affiliation]);
            $aff_id = $stmt->fetchColumn();

            if (!$aff_id && $affiliation !== '') {
                $stmt = $pdo->prepare("INSERT INTO affiliations (name) VALUES (?)");
                $stmt->execute([$affiliation]);
                $aff_id = $pdo->lastInsertId();
            }

            // チャンネル登録
            $stmt = $pdo->prepare("INSERT INTO channels (name, subscribers, affiliation_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $subscribers, $aff_id]);

            $message = 'チャンネルを追加しました！';
        } catch (PDOException $e) {
            $error = '登録エラー: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チャンネル追加</title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px;
        }
        .container {
            background: #fff;
            width: 480px;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-top: 15px;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            background-color: #e8f7e8;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin-bottom: 15px;
            color: #155724;
            border-radius: 4px;
        }
        .error {
            background-color: #fbeaea;
            border-left: 4px solid #dc3545;
            padding: 10px;
            margin-bottom: 15px;
            color: #721c24;
            border-radius: 4px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📺 チャンネル追加</h1>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="add.php">
            <label>チャンネル名：</label>
            <input type="text" name="name" placeholder="例：Vスポ公式チャンネル">

            <label>登録者数：</label>
            <input type="number" name="subscribers" placeholder="例：120000">

            <label>所属（任意）：</label>
            <input type="text" name="affiliation" placeholder="例：ぶいすぽっ！">

            <input type="submit" value="追加する">
        </form>

        <div class="back-link">
            <a href="initial_screen.php">← トップに戻る</a>
        </div>
    </div>
</body>
</html>
