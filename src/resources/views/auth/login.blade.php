@extends('layouts.guest')

@section('title')
ログイン
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/login.css')}}">
@endsection

@section('content')
<div class="login-form">
    <div class="login-form__heading">
        <h1>ログイン</h1>
    </div>
    <div class="login-form__content">
        <form class="form" action="/login" method="post">
            @csrf
            <div class="form__group">
                <div class="form__group-title">
                    <label for="email"><span class="form__label--item">メールアドレス</span></label>
                </div>
                <div class="form-group__content">
                    <div class="form__input--text">
                        <input type="email" name="email" id="email" value="{{old('email')}}">
                    </div>
                    <div class="form__error">
                        @error('email')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form__group">
                <div class="form__group-title">
                    <label for="password"><span class="form__label--item">パスワード</span></label>
                </div>
                <div class="form-group__content">
                    <div class="form__input--text">
                        <input type="password" name="password" id="password">
                    </div>
                    <div class="form__error">
                        @error('password')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form__button">
                <button class="form__button-submit" type="submit">ログインする</button>
            </div>

            <div class="register__link">
                <a class="register__link-submit" href="/register">会員登録はこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection