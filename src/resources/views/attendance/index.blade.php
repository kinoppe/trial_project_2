@extends('layouts.app')

@section('title')
勤怠登録
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/index.css')}}">
@endsection

@section('content')
<div class="attendance">
    <div class="attendance__status">
        {{$statusLabel}}
    </div>

    <div class="attendance__date">
        {{now()->isoFormat('YYYY年M月D日(dd)')}}
    </div>

    <div class="attendance__time">
        {{now()->format('H:i')}}
    </div>

    <div class="attendance__buttons">
        @if($status === 'off_work')
        <form action="/attendance" method="post">
            @csrf
            <button class="attendance__button">出勤</button>
        </form>
        @endif

        <div class="attendance-working">
            @if($status === 'working')
            <form action="/attendance/update" method="post">
                @csrf
                <button class="attendance__button">退勤</button>
            </form>
            <form action="/attendance/break_start" method="post">
                @csrf
                <button class="break__button">休憩入</button>
            </form>
            @endif
        </div>

        @if($status === 'on_break')
        <form action="/attendance/break_end" method="post">
            @csrf
            <button class="break__button">休憩戻</button>
        </form>
        @endif

        @if($status === 'after_work')
        <p class="clock-out">お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection