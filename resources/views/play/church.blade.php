@extends('layouts.play')
@section('title', 'Church')
@section('content')
<h1>Church</h1>
        <p>Faith: {{ optional(optional(optional($see)->churchProvince)->faith)->name }} · Standing with the see: {{ optional($relation)->standing ?? 'n/a' }}</p>
<div class="grid">
    <div class="card">
        <h3>Diocese</h3>
        <p>{{ optional($see)->name }} seated at {{ optional(optional($see)->seat)->name }}</p>
    </div>
    <div class="card">
        <h3>Papacy</h3>
        <p>{{ optional($papacy)->seat_name }} · {{ optional($papacy)->status }}</p>
        <p>Holder: {{ optional(optional(optional($papacy)->papalOffice)->currentHoldership)->holder->displayName() ?? 'vacant' }}</p>
    </div>
    <div class="card">
        <h3>Monastery</h3>
        <p>{{ optional($monastery)->name }} ({{ optional($monastery)->rule }})</p>
        <p>Religious: {{ optional($monastery)->religious_population }} · Stores: {{ optional($monastery)->stores }}</p>
        <p>Place: {{ optional(optional($monastery)->territory)->name }}</p>
    </div>
</div>
<h3>Offices (not titles)</h3>
<table>
    <tr><th>Office</th><th>Key</th><th>Holder</th></tr>
    @foreach($offices as $office)
        <tr>
            <td>{{ $office->name }}</td>
            <td>{{ $office->key ?? $office->office_key }}</td>
            <td>{{ optional(optional($office->currentHoldership)->holder)->displayName() ?? 'vacant' }}</td>
        </tr>
    @endforeach
</table>
@endsection
