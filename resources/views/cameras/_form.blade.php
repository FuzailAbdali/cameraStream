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
