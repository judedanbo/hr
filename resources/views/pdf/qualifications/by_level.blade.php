@extends('pdf.qualifications.layout')

@section('title', 'Qualifications by Level')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Level</th>
                <th>Staff Count</th>
                <th>% of Workforce</th>
                <th style="width:30%">Distribution</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($levels as $lv)
            @php
                $count = $distribution[$lv->value] ?? 0;
                $pct = $totalStaff > 0 ? round(($count / $totalStaff) * 100, 1) : 0;
                $barWidth = (int) $pct;
            @endphp
            <tr>
                <td>{{ $lv->label() }}</td>
                <td>{{ $count }}</td>
                <td>{{ $pct }}%</td>
                <td><span class="bar" style="width: {{ $barWidth }}%"></span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
