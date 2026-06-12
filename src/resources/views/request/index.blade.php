@extends('layouts.app')

@section('title')
申請一覧
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/request/index.css')}}">
@endsection

@section('content')
<div class="request-list">
    <h1 class="request-list__title">| 申請一覧</h1>

    <div class="request-list__pending">
        <a href="{{ /stamp_correction_request/list('request.index', ['status' => 'pending']) }}">承認待ち</a>
        <a href="{{ route('request.index', ['status' => 'approved']) }}">承認済み</a>
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