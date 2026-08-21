@extends('layouts.play')
@section('title', 'Sign in')
@section('content')
<div class="card" style="max-width:420px;">
    <h1>Sign in</h1>
    <p class="muted">Provence, November 1347. You are the Count of Salon.</p>
    <p class="muted">Demo credentials are documented in docs/DIES_IRAE_VERTICAL_SLICE_STATUS.md.</p>
    <form method="post" action="{{ route('login') }}">
        @csrf
        <p><label>Email<br><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror></label>
            @error('email')
                <span id="email-error" class="err">{{ $message }}</span>
            @enderror
        </p>
        <p><label>Password<br><input type="password" name="password" autocomplete="current-password" required></label></p>
        <button type="submit">Enter the county</button>
    </form>
</div>
@endsection
