@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Account Settings</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> {{ auth()->user()->name }}</p>
                            <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                            <p><strong>Role:</strong>
                                <span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', auth()->user()->role ?? 'Not Assigned')) }}</span>
                            </p>
                            <p><strong>Status:</strong>
                                @if(auth()->user()->isApproved())
                                    <span class="badge badge-success">Approved</span>
                                @elseif(auth()->user()->isPending())
                                    <span class="badge badge-warning">Pending Approval</span>
                                @elseif(auth()->user()->isRejected())
                                    <span class="badge badge-danger">Rejected</span>
                                @endif
                            </p>
                            <p><strong>Member since:</strong> {{ auth()->user()->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    @if(auth()->user()->isPending())
                        <div class="alert alert-warning mt-3">
                            <h5>Account Pending Approval</h5>
                            <p>Your account is currently pending approval from an administrator. You will be able to access the system once your account is approved.</p>
                        </div>
                    @elseif(auth()->user()->isRejected())
                        <div class="alert alert-danger mt-3">
                            <h5>Account Rejected</h5>
                            <p>Your account registration has been rejected. Please contact an administrator for more information.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
