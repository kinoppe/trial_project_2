<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{asset('css/sanitize.css')}}">
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
                <img src="{{ asset('storage/icons/CoachTech_White 1 (1).png') }}">

            <nav class="header__nav">
                <a class="attendance__top" href="/attendance">勤怠</a>
                <a class="attendance__list" href="/attendance/list">勤怠一覧</a>
                <a class="attendance__detail" href="/stamp_correction_request/list">申請</a>
                <a class="attendance__report" href="/attendance/report">レポート</a>
                <form action="/logout" method="post">
                    @csrf
                    <button class="logout__link">ログアウト</button>
                </form>
            </nav>
        </div>
        
    </header>
    <main>
    @yield('content')
    </main>
</body>
</html>