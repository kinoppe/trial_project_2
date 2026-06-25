@extends('layouts.admin')

@section('title')
スタッフ別勤怠一覧
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/admin/attendance/staff.css')}}">
@endsection

@section('content')
<div class="staff-attendance">
    <h1 class="staff-attendance__title">{{$user->name}}さんの勤怠一覧</h1>

    <div class="staff-attendance__month">
        <a href="/admin/attendance/staff/{{$user->id}}?month={{$month->copy()->subMonth()->format('Y-m')}}">
            <img class="staff-attendance__icon-last" src="{{ asset('storage/icons/088deff71873c09816bca59dd0d7efa7308e8fba.png') }}"> 前月
        </a>
        <p>
            <img class="staff-attendance__icon" src="{{ asset('storage/icons/50f4850c610ecd6f85b7ef666143260b91151a78.png') }}">
            {{$month->format('Y/m')}}
        </p>
        <a href="/admin/attendance/staff/{{$user->id}}?month={{$month->copy()->addMonth()->format('Y-m')}}">翌月
            <img class="staff-attendance__icon-next" src="{{ asset('storage/icons/088deff71873c09816bca59dd0d7efa7308e8fba.png') }}">
        </a>
    </div>

    <table class="staff-attendance-table">
        <tr class="staff-attendance-table__header">
            <th>日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>

        @foreach($records as $record)
        <tr class="staff-attendance-table__description">
            <td>{{$record['date']}}</td>
            <td>{{$record['clock_in']}}</td>
            <td>{{$record['clock_out']}}</td>
            <td>{{$record['break_time']}}</td>
            <td>{{$record['total_time']}}</td>
            <td>
                <a class="staff-attendance-table__detail" href="/admin/attendance/{{$record['date_key']}}?user_id={{$user->id}}">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="export__button">
        <form action="/admin/attendance/staff/{{$user->id}}/csv" method="get">
            <input type="hidden" name="month" value="{{$month->format('Y-m')}}">
            <button class="export__button-submit" type="submit">CSV出力</button>
        </form>
    </div>
</div>
@endsection