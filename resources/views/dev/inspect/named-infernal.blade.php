@extends('layouts.inspect')
@section('title', 'Named infernal inspect')
@section('content')
<h1>Named infernal entities (true state)</h1>
<p class="muted">Local inspect only. Players do not see this page.</p>
@forelse($demons as $demon)
    <section>
        <h2>{{ $demon->true_name }} ({{ $demon->catalog_key }})</h2>
        <p>Alias: {{ $demon->public_alias }} · Strategy: {{ $demon->strategy }} · Hierarchy: {{ $demon->hierarchy }} · Status: {{ $demon->status }}</p>
        <p>Titles: {{ implode(', ', $demon->titles ?? []) }} · Epithets: {{ implode(', ', $demon->epithets ?? []) }}</p>
        <p>Themes: {{ implode(', ', $demon->themes ?? []) }} · Channels: {{ implode(', ', $demon->channels ?? []) }}</p>
        <p>Hidden: {{ json_encode($demon->hidden_true_state) }}</p>
        <p>Vulnerabilities: {{ implode('; ', $demon->vulnerabilities ?? []) }}</p>
        <p>Physical min intensity: {{ $demon->physical_manifest_min_apocalypse }} · forbidden phases: {{ implode(', ', $demon->physical_manifest_forbidden_phases ?? []) }}</p>
        <h3>Objectives</h3>
        <ul>@foreach($demon->objectives as $o)<li>{{ $o->key }} · {{ $o->status }} · {{ $o->aim }}</li>@endforeach</ul>
        <h3>Grudges</h3>
        <ul>@forelse($demon->grudges as $g)<li>{{ $g->subject_type }} {{ $g->subject_id }} · {{ $g->reason }} ({{ $g->intensity }})</li>@empty<li>none</li>@endforelse</ul>
        <h3>Knowledge granted</h3>
        <ul>@forelse($demon->knowledge as $k)<li>{{ $k->observer_key }} knows {{ $k->facet }} from {{ $k->source }}</li>@empty<li>none</li>@endforelse</ul>
        <h3>Acts</h3>
        <ul>@forelse($demon->acts as $a)<li>{{ $a->acted_on }} · {{ $a->channel }} · {{ $a->summary }}</li>@empty<li>none</li>@endforelse</ul>
    </section>
@empty
    <p>No named entities in this world.</p>
@endforelse
<p><a href="{{ route('dev.inspect.show', $world) }}">Back</a></p>
@endsection
