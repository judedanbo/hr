@extends('pdf.qualifications.layout')

@section('title', 'Qualifications: ' . $person->first_name . ' ' . $person->surname)

@section('content')
    @php
        $inst = \App\Models\InstitutionPerson::where('person_id', $person->id)->whereNull('end_date')->first();
    @endphp
    <p>
        <strong>Staff #:</strong> {{ $inst?->staff_number ?? '-' }}<br>
        <strong>Hire Date:</strong> {{ $inst?->hire_date instanceof \Carbon\Carbon ? $inst->hire_date->format('Y-m-d') : ($inst?->hire_date ?? '-') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>Qualification</th>
                <th>Level</th>
                <th>Institution</th>
                <th>Year</th>
                <th>Status</th>
                <th>Approved</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($qualifications as $q)
            <tr>
                <td>{{ $q->qualification }}</td>
                <td>{{ $q->level ? (\App\Enums\QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : '' }}</td>
                <td>{{ $q->institution }}</td>
                <td>{{ $q->year }}</td>
                <td>{{ $q->status?->label() }}</td>
                <td>{{ $q->approved_at?->format('Y-m-d') ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
