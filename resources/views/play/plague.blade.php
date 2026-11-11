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
<h3>Settlement census</h3>
@if(isset($settlements) && $settlements->isNotEmpty())
<table>
    <tr><th>Place</th><th>Souls</th><th>Nobles</th><th>Clergy</th><th>Infectious</th><th>Incubating</th><th>Dead this wave</th><th>Ruin</th></tr>
    @foreach($settlements as $place)
        @php $focus = $settlementPlague[$place->id] ?? null; @endphp
        <tr>
            <td>{{ $place->name }}</td>
            <td>{{ optional($place->population)->souls() ?? 0 }}</td>
            <td>{{ optional($place->population)->nobles ?? 0 }}</td>
            <td>{{ optional($place->population)->clergy ?? 0 }}</td>
            <td>{{ $focus->infectious ?? optional($place->population)->infectious ?? 0 }}</td>
            <td>{{ $focus->incubating ?? optional($place->population)->incubating ?? 0 }}</td>
            <td>{{ $focus->dead ?? 0 }}</td>
            <td>{{ $place->ruin_state }}</td>
        </tr>
    @endforeach
</table>
@else
<p class="muted">No settlement census yet. The wave has not been seated on a town.</p>
@endif
@endsection
