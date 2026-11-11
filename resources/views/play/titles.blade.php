@extends('layouts.play')
@section('title', 'Titles')
@section('content')
<h1>Secular titles</h1>
<p class="muted">Spiritual offices are not listed here. See Church.</p>
<table>
    <tr><th>Title</th><th>Rank</th><th>Holder</th><th>Law</th><th>Heir</th><th>Capital</th></tr>
    @forelse($titles as $title)
        <tr>
            <td>{{ $title->name }}</td>
            <td>{{ $title->rank }}</td>
            <td>{{ optional(optional($title->currentOwnership)->holder)->displayName() ?? 'vacant' }}</td>
            <td>{{ optional($title->successionLaw)->name ?? 'Male-preference primogeniture' }}</td>
            <td>
                @if(isset($heirs[$title->id]) && $heirs[$title->id]['heir'])
                    {{ $heirs[$title->id]['heir']->displayName() }}
                @else
                    <span class="muted">No living heir</span>
                @endif
            </td>
            <td>{{ optional($title->capital)->name }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">No secular titles recorded.</td></tr>
    @endforelse
</table>
<h3>Title history</h3>
@if(isset($successions) && $successions->isNotEmpty())
<table>
    <tr><th>Date</th><th>Title</th><th>From</th><th>To</th><th>Outcome</th></tr>
    @foreach($successions as $row)
        <tr>
            <td>{{ optional($row->processed_on)->toDateString() }}</td>
            <td>{{ optional($row->title)->name }}</td>
            <td>{{ optional($row->deceased)->displayName() }}</td>
            <td>{{ optional($row->heir)->displayName() ?? 'vacant' }}</td>
            <td>{{ $row->outcome }}</td>
        </tr>
    @endforeach
</table>
@else
<p class="muted">No death has moved a title yet.</p>
@endif
@endsection
