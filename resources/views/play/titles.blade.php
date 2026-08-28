@extends('layouts.play')
@section('title', 'Titles')
@section('content')
<h1>Secular titles</h1>
<p class="muted">Spiritual offices are not listed here. See Church.</p>
<table>
    <tr><th>Title</th><th>Rank</th><th>Holder</th><th>Capital</th></tr>
    @forelse($titles as $title)
        <tr>
            <td>{{ $title->name }}</td>
            <td>{{ $title->rank }}</td>
            <td>{{ optional(optional($title->currentOwnership)->holder)->displayName() ?? 'vacant' }}</td>
            <td>{{ optional($title->capital)->name }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted">No secular titles recorded.</td></tr>
    @endforelse
</table>
@endsection
