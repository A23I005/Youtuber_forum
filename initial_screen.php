<?php
// 初期画面：メニューのみ表示
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>掲示板アプリ - トップ</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background-color: #f8f9fa; margin-top: 100px; }
        h1 { color: #333; }
        .menu { display: inline-block; background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        .menu a { display: block; margin: 15px 0; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; transition: 0.3s; }
        .menu a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="menu">
        <h1>📺 掲示板アプリ</h1>
        <a href="add.php">チャンネルを追加する</a>
        <a href="select.php">チャンネルを選択する</a>
    </div>
</body>
</html>
