@extends('layouts.play')
@section('title', 'Sign in')
@section('content')
<div class="card" style="max-width:420px;">
    <h1>Sign in</h1>
    <p class="muted">Demo: <code>lord@diesirae.test</code> / <code>password</code> (Provence slice).</p>
    <p class="muted">Other seeded worlds (Europa 1347 campaign, historical map) use their own logins from the artisan seed commands.</p>
    <form method="post" action="{{ route('login') }}">
        @csrf
        <p><label>Email<br><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror></label>
            @error('email')
                <span id="email-error" class="err">{{ $message }}</span>
            @enderror
        </p>
        <p><label>Password<br><input type="password" name="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror></label>
            @error('password')
                <span id="password-error" class="err">{{ $message }}</span>
            @enderror
        </p>
        <button type="submit">Enter the county</button>
    </form>
</div>
@endsection
