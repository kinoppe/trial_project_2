@extends('layouts.app')

@section('title')
勤怠一覧
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/list.css')}}">
@endsection

@section('content')
<div class="attendance-list">
    <h1 class="attendance-list__title">| 勤怠一覧</h1>

    <div class="attendance-list__month">
        <a href="/attendance/list?month={{$month->copy()->subMonth()->format('Y-m')}}">◀︎ 前月</a>
        <p>{{$month->format('Y/m')}}</p>
        <a href="/attendance/list?month={{$month->copy()->addMonth()->format('Y-m')}}">翌月 ▶︎</a>
    </div>

    <table class="attendance-table">
        <tr class="attendance-table__header">
            <th>日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>

        @foreach($records as $record)
        <tr class="attendance-table__description">
            <td>{{$record['date']}}({{$record['week']}})</td>
            <td>{{$record['clock_in']}}</td>
            <td>{{$record['clock_out']}}</td>
            <td>{{$record['break_time']}}</td>
            <td>{{$record['total_time']}}</td>
            <td>
                <a class="attendance-table__detail" href="/attendance/detail/{{$record['date_key']}}">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection