@extends('layouts.app')

@section('title')
勤怠一覧(管理者)
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/admin/list.css')}}">
@endsection

@section('content')
<div class="admin-attendance-list">
    <h1 class="admin-attendance-list__title">| 勤怠一覧</h1>

    <div class="admin-attendance-list__day">
        <a href="/admin/attendance/list?month={{$month->copy()->subMonth()->format('Y-m')}}">
            <img class="attendance__icon-last" src="{{ asset('storage/icons/088deff71873c09816bca59dd0d7efa7308e8fba.png') }}"> 前日
        </a>
        <p>
            <img class="attendance__icon" src="{{ asset('storage/icons/50f4850c610ecd6f85b7ef666143260b91151a78.png') }}">
            {{$month->format('Y/m/d')}}
        </p>
        <a href="/admin/attendance/list?month={{$month->copy()->addMonth()->format('Y-m')}}">翌日 
            <img class="attendance__icon-next" src="{{ asset('storage/icons/088deff71873c09816bca59dd0d7efa7308e8fba.png') }}">
        </a>
    </div>

    <table class="admin-attendance-table">
        <tr class="admin-attendance-table__header">
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>

        @foreach($records as $record)
        <tr class="admin-attendance-table__description">
            <td>{{$record['date']}}({{$record['week']}})</td>
            <td>{{$record['clock_in']}}</td>
            <td>{{$record['clock_out']}}</td>
            <td>{{$record['break_time']}}</td>
            <td>{{$record['total_time']}}</td>
            <td>
                <a class="admin-attendance-table__detail" href="/attendance/detail/{{$record['date_key']}}">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection