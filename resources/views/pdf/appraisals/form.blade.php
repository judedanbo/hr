@extends('pdf.qualifications.layout')

@section('title', 'Appraisal — ' . ($appraisal->staff?->person?->full_name ?? 'Staff'))

@section('content')
    <table style="border: none; margin-bottom: 12px;">
        <tr>
            <td style="border: none;"><strong>Staff:</strong> {{ $appraisal->staff?->person?->full_name }}</td>
            <td style="border: none;"><strong>Staff No:</strong> {{ $appraisal->staff?->staff_number }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Cycle:</strong> {{ $appraisal->cycle?->name }}</td>
            <td style="border: none;"><strong>Status:</strong> {{ $appraisal->status->label() }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Appraiser:</strong> {{ $appraisal->appraiser?->person?->full_name ?? '—' }}</td>
            <td style="border: none;"><strong>Reviewer:</strong> {{ $appraisal->reviewer?->person?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Overall:</strong> {{ $appraisal->overall_score ?? '—' }} ({{ $appraisal->overall_band ?? '—' }})</td>
            <td style="border: none;"><strong>Weights:</strong> {{ $appraisal->cycle?->objectives_weight }}% / {{ $appraisal->cycle?->competencies_weight }}%</td>
        </tr>
    </table>

    <h1 style="font-size: 13px;">Objectives</h1>
    <table>
        <thead>
            <tr>
                <th>Objective</th>
                <th>Weight</th>
                <th>Self</th>
                <th>Supervisor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appraisal->objectives as $objective)
                <tr>
                    <td>{{ $objective->title }}</td>
                    <td>{{ $objective->weight }}%</td>
                    <td>{{ $objective->self_score ?? '—' }}</td>
                    <td>{{ $objective->supervisor_score ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No objectives.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h1 style="font-size: 13px; margin-top: 12px;">Competencies</h1>
    <table>
        <thead>
            <tr>
                <th>Competency</th>
                <th>Weight</th>
                <th>Self</th>
                <th>Supervisor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appraisal->competencyRatings as $rating)
                <tr>
                    <td>{{ $rating->competency?->name }}</td>
                    <td>{{ $rating->weight }}%</td>
                    <td>{{ $rating->self_score ?? '—' }}</td>
                    <td>{{ $rating->supervisor_score ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No competencies.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
