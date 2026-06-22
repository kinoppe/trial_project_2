@extends(auth()->user()->is_admin ? 'layouts.admin' : 'layouts.app')

@section('title')
申請承認
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/admin/request/approve.css')}}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <table class="attendance-detail__table">
        <tr class="detail-table-row">
            <th>名前</th>
            <td>{{$correctionRequest->attendance->user->name}}</td>
        </tr>

        <tr class="detail-table-row">
            <th>日付</th>
            <td class="detail-table__date">
                <span>{{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('Y年') }}</span>
                <span>{{ \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('n月j日') }}</span>
            </td>
        </tr>

        <tr class="detail-table-row">
            <th>出勤・退勤</th>
            <td class="detail-table__time">
                <span>{{ \Carbon\Carbon::parse($correctionRequest->request_clock_in)->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ \Carbon\Carbon::parse($correctionRequest->request_clock_out)->format('H:i') }}</span>
            </td>
        </tr>

        @foreach($correctionRequest->breaks as $index => $break)
        <tr class="detail-table-row">
            @if($index === 0)
            <th>休憩</th>
            @else
            <th>休憩{{$index + 1}}</th>
            @endif
            <td class="detail-table__time">
                <span>{{ \Carbon\Carbon::parse($break->break_start)->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ \Carbon\Carbon::parse($break->break_end)->format('H:i') }}</span>
            </td>
        </tr>
        @endforeach

        <tr class="detail-table-row">
            <th>備考</th>
            <td>{{$correctionRequest->note}}</td>
        </tr>
    </table>

    @if($isAdmin && $correctionRequest->status === 'pending')
        <form action="/stamp_correction_request/approve/{{ $correctionRequest->id }}" method="post">
            @csrf
            <div class="attendance-detail__button">
                <button class="attendance-detail__button-submit" type="submit">
                    承認
                </button>
            </div>
        </form>

    @elseif(!$isAdmin && $correctionRequest->status === 'pending')
        <p class="attendance-detail__pending-message">*承認待ちのため修正はできません。</p>

    @else
        <div class="request-approve">
            <span class="approved">承認済み</span>
        </div>
    @endif
</div>
@endsection