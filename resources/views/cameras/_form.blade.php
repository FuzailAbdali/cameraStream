@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $camera->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">IP Address</label>
        <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $camera->ip_address ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">External IP (optional)</label>
        <input type="text" name="external_ip" class="form-control" value="{{ old('external_ip', $camera->external_ip ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Port</label>
        <input type="number" name="port" class="form-control" value="{{ old('port', $camera->port ?? 554) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">RTSP Scheme</label>
        <select name="rtsp_scheme_id" class="form-select">
            <option value="">Manual RTSP Path (no scheme)</option>
            @foreach ($rtspSchemes as $scheme)
                <option value="{{ $scheme->id }}" @selected((string) old('rtsp_scheme_id', $camera->rtsp_scheme_id ?? '') === (string) $scheme->id)>
                    {{ $scheme->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-12">
        <label class="form-label">RTSP Path (fallback if no scheme selected)</label>
        <input
            type="text"
            name="rtsp_path"
            class="form-control"
            value="{{ old('rtsp_path', $camera->rtsp_path ?? '') }}"
            placeholder="stream"
        >
        <small class="text-muted">Used only when no RTSP scheme is selected.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $camera->username ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ isset($camera) ? '(leave blank to keep current)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ isset($camera) ? '' : 'required' }}>
    </div>
</div>

<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Camera</button>
    <a class="btn btn-secondary" href="{{ route('cameras.index') }}">Cancel</a>
</div>
