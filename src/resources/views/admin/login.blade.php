@extends('layouts.guest')

@section('title')
管理者ログイン
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/admin/login.css')}}">
@endsection

@section('content')
<div class="admin-login-form">
    <div class="admin-login-form__heading">
        <h1>管理者ログイン</h1>
    </div>
    <div class="admin-form__content">
        <form class="form" action="/admin/login" method="post">
            @csrf
            <div class="admin-form__group">
                <div class="admin-form__group-title">
                    <label for="email"><span class="form__label--item">メールアドレス</span></label>
                </div>
                <div class="admin-form-group__content">
                    <div class="admin-form__input--text">
                        <input type="email" name="email" id="email" value="{{old('email')}}">
                    </div>
                    <div class="form__error">
                        @error('email')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>

            <div class="admin-form__group">
                <div class="admin-form__group-title">
                    <label for="password"><span class="form__label--item">パスワード</span></label>
                </div>
                <div class="admin-form-group__content">
                    <div class="admin-form__input--text">
                        <input type="password" name="password" id="password">
                    </div>
                    <div class="form__error">
                        @error('password')
                        {{$message}}
                        @enderror
                    </div>
                </div>
            </div>

            <div class="admin-form__button">
                <button class="admin-form__button-submit" type="submit">ログインする</button>
            </div>
        </form>
    </div>
</div>
@endsection