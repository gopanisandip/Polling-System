@extends('layouts.app')

@section('title', 'Register - Poll System')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h1 class="h3 font-weight-bold">Sign Up</h1>
                    <p class="text-muted">Create account for poll system</p>
                </div>

                <form method="POST" action="{{ route('register') }}" id="register-form">
                    @csrf

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="Enter your name..">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="Enter your email...">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Enter your password..">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="form-control" placeholder="Confirm your password...">
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="register_as_admin" value="1" class="custom-control-input" id="register_as_admin" {{ old('register_as_admin') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="register_as_admin">Register as Admin</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Create Account
                    </button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Have an account?
                    <a href="{{ route('login') }}">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
