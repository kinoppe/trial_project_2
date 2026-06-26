@extends('layouts.guest')

@section('title')
メール認証
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/verify-email.css')}}">
@endsection

@section('content')
<div class="verify-email">
    <div class="verify-email__inner">
        <p class="verify-email__text">登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。</p>
    

        <a class="verify-email__link" href="http://localhost:8025" target="_blank">
            認証はこちらから
        </a>

        <form action="{{ route('verification.send') }}" method="post">
            @csrf
            <button class="verify-email__resend" type="submit">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection