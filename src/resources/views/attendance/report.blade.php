@extends('layouts.app')

@section('title')
マイ勤怠レポート
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/report.css')}}">
@endsection

@section('content')
<div class="my-report">
    <h1 class="my-report__title">マイ勤怠レポート</h1>
    <p class="my-report__text">過去６ヶ月のデータから集計しています。</p>

    <section class="my-report-section">
        <h2 class="my-report-section__title">基本サマリー</h2>
        <div class="summary-cards">
            <div class="summary-card">
                <p class="summary-card__label">総労働時間</p>
                <p class="summary-card__value">{{$totalWork}}</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">総残業時間</p>
                <p class="summary-card__value">{{$totalOver}}</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">平均労働時間</p>
                <p class="summary-card__value">{{$averageWork}}</p>
            </div>
        </div>
    </section>

    <section class="my-report-section">
        <h2 class="my-report-section__title">月次推移（過去６ヶ月）</h2>
        <table class="my-report__table">
            <tr>
                <th>月</th>
                <th>労働時間</th>
                <th>残業時間</th>
            </tr>
            @foreach($monthlyReports as $report)
            <tr>
                <td>{{$report['month']}}</td>
                <td>{{$report['work_time']}}</td>
                <td>{{$report['over_time']}}</td>
            </tr>
            @endforeach
        </table>
    </section>

    <section class="my-report-section">
        <h2 class="my-report-section__title">今月の異常検知</h2>
        <p class="my-report-section__standard-time">
            基準: 始業 09:00 / 終業 18:00 / 長時間労働は１日１０時間超
        </p>
        <div class="summary-cards">
            <div class="summary-card">
                <p class="summary-card__label">遅刻回数</p>
                <p class="summary-card__value">{{$lateCount}}</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">早退回数</p>
                <p class="summary-card__value">{{$earlyCount}}</p>
            </div>
            <div class="summary-card">
                <p class="summary-card__label">長時間労働日数</p>
                <p class="summary-card__value">{{$longWorkCount}}</p>
            </div>
        </div>
    </section>
</div>
@endsection