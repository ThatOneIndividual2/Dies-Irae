@extends('layouts.play')
@section('title', 'Army')
@section('content')
<h1>Host</h1>
@if($home)
<form method="post" action="{{ route('army.raise') }}">
    @csrf
    Raise levy at {{ $home->name }} (available {{ $home->levy_available }}):
    <label>Raise levy strength
        <input type="number" name="strength" value="80" min="1" max="{{ max(1,$home->levy_available) }}">
    </label>
    <button>Raise army</button>
</form>
@else
<p class="muted">No home seat is set, so levy raise is unavailable.</p>
@endif
@if($armies->isEmpty())
<p class="muted">No armies in the field.</p>
@else
<table>
    <tr><th>Name</th><th>Kind</th><th>Owner</th><th>Place</th><th>Strength</th><th>Act</th></tr>
    @foreach($armies as $army)
        <tr>
            <td>{{ $army->name }}</td>
            <td>{{ $army->kind }}</td>
            <td>{{ optional($army->owner)->displayName() ?? 'none' }}</td>
            <td>{{ $army->territory->name }}</td>
            <td>{{ $army->strength }} {{ $army->is_active ? '' : '(destroyed)' }}</td>
            <td>
                @if($army->is_active && (int)$army->owner_character_id === (int)$play->ruler->id)
                    <form method="post" action="{{ route('army.move', $army) }}">
                        @csrf
                        <label>March destination
                            <select name="territory_id">
                                @foreach($army->territory->neighbors as $n)
                                    <option value="{{ $n->id }}">{{ $n->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button>March</button>
                    </form>
                    @foreach($armies->where('territory_id', $army->territory_id)->where('is_active', true) as $other)
                        @if($other->id !== $army->id && (int)$other->owner_character_id !== (int)$play->ruler->id)
                            <form method="post" action="{{ route('army.fight', $army) }}">
                                @csrf
                                <input type="hidden" name="enemy_army_id" value="{{ $other->id }}">
                                <button>Fight {{ $other->name }}</button>
                            </form>
                        @endif
                    @endforeach
                @endif
            </td>
        </tr>
    @endforeach
</table>
@endif
<h3>Battles</h3>
@if($battles->isEmpty())
<p class="muted">No battles recorded.</p>
@else
<table>
    <tr><th>Date</th><th>Place</th><th>Kind</th><th>Winner</th><th>Losses</th></tr>
    @foreach($battles as $b)
        <tr>
            <td>{{ $b->fought_on }}</td>
            <td>{{ $b->territory->name }}</td>
            <td>{{ $b->kind }}</td>
            <td>{{ $b->winner }}</td>
            <td>{{ $b->attacker_strength_before }}→{{ $b->attacker_strength_after }} vs {{ $b->defender_strength_before }}→{{ $b->defender_strength_after }}</td>
        </tr>
    @endforeach
</table>
@endif
@endsection
