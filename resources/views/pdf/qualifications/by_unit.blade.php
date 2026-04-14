@extends('pdf.qualifications.layout')

@section('title', 'Qualifications by Unit')

@section('content')
    <table>
        <thead>
        <tr>
            <th>Unit</th>
            @foreach ($levels as $lv)
                <th>{{ $lv->label() }}</th>
            @endforeach
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($byUnit as $unitName => $counts)
            @php $total = array_sum($counts); @endphp
            <tr>
                <td>{{ $unitName }}</td>
                @foreach ($levels as $lv)
                    <td>{{ $counts[$lv->value] ?? 0 }}</td>
                @endforeach
                <td><strong>{{ $total }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
