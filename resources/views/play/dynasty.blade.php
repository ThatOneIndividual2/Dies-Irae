@extends('layouts.play')
@section('title', 'Dynasty')
@section('content')
<h1>{{ $dynasty->name ?? 'No dynasty' }}</h1>
@if($dynasty)
<p class="muted">{{ $dynasty->motto }} · {{ $dynasty->is_extinct ? 'extinct' : 'living' }}</p>
<h3>Houses</h3>
@if($dynasty->houses->isEmpty())
<p class="muted">No houses recorded.</p>
@else
<ul>@foreach($dynasty->houses as $house)<li>{{ $house->name }}</li>@endforeach</ul>
@endif
<h3>Kin</h3>
@if($dynasty->characters->isEmpty())
<p class="muted">No kin recorded.</p>
@else
<table>
    <tr><th>Name</th><th>Alive</th><th>Residence</th></tr>
    @foreach($dynasty->characters as $person)
        <tr><td>{{ $person->displayName() }}</td><td>{{ $person->is_alive ? 'yes' : 'no' }}</td><td>{{ optional($person->residence)->name }}</td></tr>
    @endforeach
</table>
@endif
@endif
@endsection
