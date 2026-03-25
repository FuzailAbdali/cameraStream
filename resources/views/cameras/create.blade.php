@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Create Camera</h1>
<div class="card p-3">
    <form action="{{ route('cameras.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">IP Address</label>
                <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">External IP (optional)</label>
                <input type="text" name="external_ip" class="form-control" value="{{ old('external_ip') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Port</label>
                <input type="number" name="port" class="form-control" value="{{ old('port', 554) }}" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">RTSP Path (optional)</label>
                <input
                    type="text"
                    name="rtsp_path"
                    class="form-control"
                    value="{{ old('rtsp_path') }}"
                    placeholder="stream"
                >
                <small class="text-muted">Different cameras use different RTSP paths. Examples: stream, h264/ch1/main/av_stream, cam/realmonitor?channel=1&subtype=0</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Save Camera</button>
            <a class="btn btn-secondary" href="{{ route('cameras.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
