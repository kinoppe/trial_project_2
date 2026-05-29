@extends('layouts.guest')

@section('title')
会員登録
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/auth/register.css')}}">
@endsection

@section('content')
<div class="register-form">
    <div class="register-form__heading">
        <h1>会員登録</h1>
    </div>
    <div class="register-form__content">
        <form class="form" action="/register" method="post">
            @csrf
            <div class="form__group">
                <div class="form__group-title">
                    <label for="name"><span class="form__label--item">名前</span></label>
                </div>
                <div class="form-group__content">
                    <div class="form__input--text">
                        <input type="text" name="name" id="name" value="{{old('name')}}">
                    </div>
                    <div class="form__error">
                        @error('name')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>

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

            <div class="form__group">
                <div class="form__group-title">
                    <label for="password_confirmation"><span class="form__label--item">パスワード確認</span></label>
                </div>
                <div class="form-group__content">
                    <div class="form__input--text">
                        <input type="password" name="password_confirmation" id="password_confirmation">
                    </div>
                    <div class="form__error">
                        @error('password_confirmation')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form__button">
                <button class="form__button-submit" type="submit">登録する</button>
            </div>

            <div class="login__link">
                <a class="login__link-submit" href="/login">ログインはこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection