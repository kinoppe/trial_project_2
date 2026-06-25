# 勤怠管理アプリ

## 環境構築

Dockerビルド
1. git clone
git@github.com:Estra-Coachtech/laravel-docker-template.git
2. リポジトリ名を変更
mv laravel-docker-template trial_project_2
3. リモートURLを変更
git remote set-url origin git@github.com:kinoppe/trial_project_2.git
4. Dockerコンテナを起動
docker-compose up -d --build

## Laravel 環境構築

1. docker-compose exec php bash
2. composer install
3. cp .env.example .env , 環境変数を適宜変更
DB_HOST=mysql
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
4. php artisan key:generate
5. php artisan migrate
6. php artisan db:seed
7. php artisan storage:link



## 使用技術

・PHP 8.1.34
・Laravel 8.83.29
・MySQL 8.0.36
・nginx 1.21.1

## ER図

<img src="">

## URL

開発環境
・会員登録画面（一般ユーザー）：http://localhost/register
・ログイン画面（一般ユーザー）：http://localhost/login
・出勤登録画面（一般ユーザー）：http://localhost/attendance
・勤怠一覧画面（一般ユーザー）：http://localhost/attendance/list
・勤怠詳細画面（一般ユーザー）：http://localhost/attendance/detail/{date}
・申請一覧画面（一般ユーザー）：http://localhost/attendance/stamp_correction_request/list
・ログイン画面（管理者）：http://localhost/admin/login
・勤怠一覧画面（管理者）：http://localhost/admin/attendance/list
・勤怠詳細画面（管理者）：http://localhost/admin/attendance/{date}
・スタッフ一覧画面（管理者）：http://localhost/admin/staff/list
・スタッフ別勤怠一覧画面（管理者）：http://localhost/admin/attendance/staff{id}
・申請一覧画面（管理者）：http://localhost/attendance/stamp_correction_request/list
・修正申請承認画面（管理者）：http://localhost//stamp_correction_request/approve/{attendance_correct_request_id}
・phpMyAdmin：http://localhost:8080/

