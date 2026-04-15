@extends('pdf.qualifications.layout')

@section('title', 'Staff Without Recorded Qualifications')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Staff #</th>
                <th>Name</th>
                <th>Hire Date</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($staff as $person)
            @php
                $inst = \App\Models\InstitutionPerson::where('person_id', $person->id)->whereNull('end_date')->first();
            @endphp
            <tr>
                <td>{{ $inst?->staff_number }}</td>
                <td>{{ $person->first_name }} {{ $person->surname }}</td>
                <td>{{ $inst?->hire_date instanceof \Carbon\Carbon ? $inst->hire_date->format('d M Y') : $inst?->hire_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
