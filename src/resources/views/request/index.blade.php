@extends('layouts.app')

@section('title')
申請一覧
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/request/index.css')}}">
@endsection

@section('content')
<div class="request-list">
    <h1 class="request-list__title">申請一覧</h1>

    <div class="request-list__tabs">
        <a class="{{ $status === 'pending' ? 'active' : '' }}"
        href="/stamp_correction_request/list?status=pending">
            承認待ち
        </a>
        <a class="{{ $status === 'approved' ? 'active' : '' }}"
        href="/stamp_correction_request/list?status=approved">
            承認済み
        </a>
    </div>

    <table class="request-table">
        <tr class="request-table__header">
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>

        @foreach($requests as $request)
        <tr class="request-table__description">
            <td>{{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
            <td>{{ $request->attendance->user->name }}</td>
            <td>{{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}</td>
            <td>{{ $request->note }}</td>
            <td>{{ $request->created_at->format('Y/m/d') }}</td>
            <td>
                @if(auth()->user()->is_admin)
                    <a class="request-table__detail"
                    href="/stamp_correction_request/approve/{{ $request->id }}">
                        詳細
                    </a>
                @else
                    <a class="request-table__detail"
                    href="/stamp_correction_request/approve/{{ $request->id }}">
                        詳細
                    </a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection