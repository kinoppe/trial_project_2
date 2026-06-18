@extends('layouts.admin')

@section('title',)
スタッフ一覧
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/index.css') }}">
@endsection

@section('content')
<div class="staff-list">
    <h1 class="staff-list__title">スタッフ一覧</h1>

    <table class="staff-list__table">
        <tr class="staff-list__table-head">
            <th>名前</th>
            <th>メールアドレス</th>
            <th>月次勤怠</th>
        </tr>

        @foreach($users as $user)
        <tr class="staff-list__table-description">
            <td>{{$user->name}}</td>
            <td>{{$user->email}}</td>
            <td>
                <a class="staff-list__detail"href="/admin/attendance/staff/{{$user->id}}">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection