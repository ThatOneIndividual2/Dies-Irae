@extends('layouts.play')
@section('title', 'Apocalypse')
@section('content')
<h1>Apocalypse</h1>
@if(!empty($isCampaign) && !empty($view))
    <p><strong>{{ $view['headline'] }}</strong></p>
    <p>{{ $view['body'] }}</p>
    <h3>What has been written</h3>
    <ol>
    @forelse ($view['evidences'] as $row)
        <li>{{ $row->recorded_on?->toDateString() }} · {{ $row->interpretation }} ({{ $row->certainty }})</li>
    @empty
        <li>The clerks have nothing unusual to copy.</li>
    @endforelse
    </ol>
@elseif ($current)
    <p>Phase: <strong>{{ $current->phase_key ?? $current->stage }}</strong> ({{ $current->stage }})</p>
    <p>Pressure: {{ $current->pressure }}</p>
    <p>Church cohesion: {{ $current->church_cohesion }} · Despair: {{ $current->despair }} · Plague: {{ $current->plague_severity }} · Manifestation: {{ $current->demonic_manifestation }}</p>
    <p>Signs: {{ implode(', ', $current->signs ?? []) ?: 'none' }}</p>
@else
    <p>The age has not been recorded.</p>
@endif
<h3>Chronicle</h3>
<ol>
@forelse ($history as $row)
    <li>{{ $row->world_date?->toDateString() }} · {{ $row->entry_type }} · {{ $row->title }}</li>
@empty
    <li>Nothing has been written yet.</li>
@endforelse
</ol>
@endsection
