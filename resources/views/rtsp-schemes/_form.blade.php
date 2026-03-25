@csrf
<div class="mb-3">
    <label class="form-label">Scheme Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $rtspScheme->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Scheme Template</label>
    <textarea name="scheme_template" class="form-control" rows="4" required>{{ old('scheme_template', $rtspScheme->scheme_template ?? '') }}</textarea>
    <small class="text-muted d-block mt-2">
        Required placeholders: <code>{username}</code>, <code>{password}</code>, <code>{ip}</code>, <code>{port}</code>.
        Example: <code>rtsp://{username}:{password}@{ip}:{port}/h264/ch1/main/av_stream</code>
    </small>
</div>

<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Scheme</button>
    <a class="btn btn-secondary" href="{{ route('rtsp-schemes.index') }}">Cancel</a>
</div>
