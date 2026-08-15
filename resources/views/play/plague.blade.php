@extends('layouts.play')
@section('title', 'Plague')
@section('content')
<h1>Plague</h1>
@forelse($waves as $wave)
    <div class="card">
        <h3>{{ $wave->name }} ({{ $wave->strain }})</h3>
        <p>Started {{ optional($wave->started_date)->toDateString() ?? $wave->started_on }} · {{ $wave->status }}</p>
        <ul>
            @foreach($wave->territoryStates as $state)
                <li>{{ $state->territory->name }}: intensity {{ $state->intensity }}, arrived {{ $state->arrived_date ?? $state->arrived_on }}</li>
            @endforeach
        </ul>
    </div>
@empty
    <p class="muted">No wave has been recorded. Advance the calendar.</p>
@endforelse
@endsection
