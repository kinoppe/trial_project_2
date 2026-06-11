@extends('layouts.app')

@section('title')
勤怠一覧
@endsection

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/show.css')}}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">| 勤怠詳細</h1>

    @if($pendingRequest)
    <table class="attendance-detail__table">
        <tr class="detail-table-row">
            <th>名前</th>
            <td>{{$attendance?->user?->name ?? auth()->user()->name}}</td>
        </tr>

        <tr class="detail-table-row">
            <th>日付</th>
            <td class="detail-table__date">
                <span>{{ \Carbon\Carbon::parse($date)->format('Y年') }}</span>
                <span>{{ \Carbon\Carbon::parse($date)->format('n月j日') }}</span>
            </td>
        </tr>

        <tr class="detail-table-row">
            <th>出勤・退勤</th>
            <td class="detail-table__time">
                <span>{{ \Carbon\Carbon::parse($pendingRequest->request_clock_in)->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ \Carbon\Carbon::parse($pendingRequest->request_clock_out)->format('H:i') }}</span>
            </td>
        </tr>

        @foreach($pendingRequest->breaks as $index => $break)
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
            <td>{{$pendingRequest->note}}</td>
        </tr>
    </table>

    <p class="attendance-detail__pending-message">*承認待ちのため修正はできません。</p>

    @else
    <form action="/attendance/detail/{{$date}}" method="post">
        @csrf
        <table class="attendance-detail__table">
            <tr class="detail-table-row">
                <th>名前</th>
                <td>{{$attendance?->user?->name ?? auth()->user()->name}}</td>
            </tr>

            <tr class="detail-table-row">
                <th>日付</th>
                <td class="detail-table__date">
                    <span>{{ \Carbon\Carbon::parse($date)->format('Y年') }}</span>
                    <span>{{ \Carbon\Carbon::parse($date)->format('n月j日') }}</span>
                </td>
            </tr>

            <tr class="detail-table-row">
                <th>出勤・退勤</th>
                <td>
                    <div class="detail-table__time">
                    <input type="time" name="clock_in"
                    value="{{ old('clock_in', $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}">
                    <span>〜</span>
                    <input type="time" name="clock_out"
                    value="{{ old('clock_out', $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}">
                    </div>
                    <div class="form-error">
                        @error('clock_in')
                        {{$message}}
                        @enderror
                    </div>
                </td>
            </tr>

            @foreach($attendance?->breakTimes ?? [] as $index => $break)
            <tr class="detail-table-row">
                @if($index === 0)
                <th>休憩</th>
                @else
                <th>休憩{{$index + 1}}</th>
                @endif
                <td>
                    <div class="detail-table__time">
                        <input type="time" name="breaks[{{$index}}][break_start]"
                        value="{{ old("breaks.$index.break_start", $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}">
                        <span>〜</span>
                        <input type="time" name="breaks[{{ $index }}][break_end]"
                        value="{{ old("breaks.$index.break_end", $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}">
                    </div>
                    <div class="form-error">
                    @error("breaks.$index.break_start")
                        {{$message}}
                    @enderror
                    </div>

                    <div class="form-error">
                    @error("breaks.$index.break_end")
                        {{$message}}
                    @enderror
                    </div>
                    
                </td>
            </tr>
            @endforeach
            @php
            $nextIndex = $attendance?->breakTimes->count() ?? 0;
            @endphp

            <tr class="detail-table-row">
                <th>休憩{{$nextIndex + 1}}</th>
                <td>
                    <div class="detail-table__time">
                    <input type="time" name="breaks[{{$nextIndex}}][break_start]"
                    value="{{old("breaks.$nextIndex.break_start")}}">
                    <span>〜</span>
                    <input type="time" name="breaks[{{$nextIndex}}][break_end]"
                    value="{{old("breaks.$nextIndex.break_end")}}">
                    </div>

                    <div class="form-error">
                    @error("breaks.$nextIndex.break_start")
                        {{$message}}
                    @enderror
                    </div>
                    <div class="form-error">
                    @error("breaks.$nextIndex.break_end")
                        {{$message}}
                    @enderror
                    </div>
                </td>
            </tr>
            <tr class="detail-table-row">
                <th>備考</th>
                <td>
                    <textarea class="detail-table__note" name="note" id="">{{old('note')}}</textarea>
                    <div class="form-error">
                    @error('note')
                        {{$message}}
                    @enderror
                    </div>
                </td>
            </tr>
        </table>

        <div class="attendance-detail__button">
            <button class="attendance-detail__button-submit" type="submit">修正</button>
        </div>
    </form>
    @endif
</div>
@endsection