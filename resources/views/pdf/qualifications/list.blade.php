@extends('pdf.qualifications.layout')

@section('title', 'Staff Qualifications List')

@section('content')
    <table>
        <thead>
        <tr>
            <th>Staff #</th><th>Name</th><th>Qualification</th><th>Level</th>
            <th>Institution</th><th>Year</th><th>Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $q)
            <tr>
                <td>{{ $q->staff_number }}</td>
                <td>{{ $q->person?->first_name }} {{ $q->person?->surname }}</td>
                <td>{{ $q->qualification }}</td>
                <td>{{ $q->level ? (\App\Enums\QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : '' }}</td>
                <td>{{ $q->institution }}</td>
                <td>{{ $q->year }}</td>
                <td>{{ $q->status?->label() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
