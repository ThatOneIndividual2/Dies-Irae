@extends('layouts.play')
@section('title', 'Dynasty')
@section('content')
<h1>{{ $dynasty->name ?? 'No dynasty' }}</h1>
@if($dynasty)
<p class="muted">{{ $dynasty->motto }} · {{ $dynasty->is_extinct ? 'extinct' : 'living' }}</p>
<h3>Houses</h3>
<ul>@foreach($dynasty->houses as $house)<li>{{ $house->name }}</li>@endforeach</ul>
<h3>Kin</h3>
<table>
    <tr><th>Name</th><th>Alive</th><th>Residence</th></tr>
    @foreach($dynasty->characters as $person)
        <tr><td>{{ $person->displayName() }}</td><td>{{ $person->is_alive ? 'yes' : 'no' }}</td><td>{{ $person->residence_territory_id }}</td></tr>
    @endforeach
</table>
@endif
@endsection
