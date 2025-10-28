# 📘 YouTuber情報掲示板システム

## 概要
このプロジェクトは、YouTuberの情報・コメント・所属・登録者数を管理する
**シンプルなPHP + MySQL（MariaDB）製掲示板システム** です。

チャンネルごとのコメント投稿や登録者数の変更が可能で、
YouTuberの活動情報を共有することを目的としています。

---

## 環境要件

| 項目 | バージョン / 推奨 |
|------|------------------|
| PHP | 8.1 以上 |
| MySQL / MariaDB | 10.4 以上 |
| Webサーバー | XAMPP / MAMP / Apache |
| ブラウザ | Google Chrome 最新版推奨 |

---

## ディレクトリ構成

```
/htdocs/
│
├─ add.php              # コメント追加処理
├─ select.php           # チャンネル選択画面
├─ print.php            # チャンネル詳細・コメント一覧・登録者数変更
└─ initial_screen.php   # 初期トップ画面（説明や導線など）
```

---

## データベース構成（データベース名：`youtube`）

### テーブル1：`affiliations`（所属情報）
| カラム名 | 型 | 備考 |
|-----------|----|------|
| `id` | INT (PK, AUTO_INCREMENT) | 所属ID |
| `name` | VARCHAR(255) | 所属名（例：UUUM、胃甲家など） |

### テーブル2：`channels`（YouTuberチャンネル情報）
| カラム名 | 型 | 備考 |
|-----------|----|------|
| `id` | INT (PK, AUTO_INCREMENT) | チャンネルID |
| `name` | VARCHAR(255) | チャンネル名 |
| `subscribers` | INT | 登録者数 |
| `affiliation_id` | INT (FK → affiliations.id) | 所属ID |
| `updated_at` | DATETIME | 更新日時 |

### テーブル3：`comments`（コメント情報）
| カラム名 | 型 | 備考 |
|-----------|----|------|
| `id` | INT (PK, AUTO_INCREMENT) | コメントID |
| `channel_id` | INT (FK → channels.id) | コメント対象チャンネル |
| `user_name` | VARCHAR(255) | 投稿者名 |
| `message` | TEXT | コメント内容 |
| `created_at` | DATETIME | 投稿日時 |

---

## 各PHPファイルの説明

### `initial_screen.php`
- 最初に表示されるトップ画面。
- 「チャンネル一覧を見る」ボタンなどから `select.php` へ遷移します。

### `select.php`
- 所属グループ（affiliation）を選び、その所属チャンネル一覧を表示。
- チャンネルをクリックすると `print.php` に遷移し、詳細が見られます。

### `print.php`
- 選択したチャンネルの詳細ページ。
- 表示内容：
  - チャンネル名・所属名
  - 登録者数（編集ボタンからインライン編集可能）
  - コメント一覧
  - コメント投稿フォーム

### `add.php`
- コメント投稿処理を行うサーバーサイドスクリプト。
- `POST` された内容（`channel_id`, `user_name`, `message`）を `comments` に保存。
- 処理後は `print.php` にリダイレクトして結果を表示します。

---

## データベース作成SQL（参考）

```sql
CREATE DATABASE youtube CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE youtube;

CREATE TABLE affiliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subscribers INT DEFAULT 0,
    affiliation_id INT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliation_id) REFERENCES affiliations(id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    user_name VARCHAR(255),
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id)
);
```

---

## 起動手順
- 1.xamppを起動し、ApacheとMySQLのStartボタンを押して起動させる
- 2.ブラウザを開いてアドレスバーにlocalhostと入力する
- 3.initial_screen.phpを開く

## 今後の拡張案
- 登録者数の増減履歴を別テーブルで管理  
- コメントに「いいね」機能を追加  
- コメント一覧の検索・ソート・絞り込みの導入  
- 所属単位での集計ランキング  
