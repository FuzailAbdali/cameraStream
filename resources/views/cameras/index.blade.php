@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Cameras</h1>
    <a href="{{ route('cameras.create') }}" class="btn btn-primary">Add Camera</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>IP</th>
                    <th>External IP</th>
                    <th>Port</th>
                    <th>RTSP Path</th>
                    <th>Stream Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cameras as $camera)
                    <tr data-camera-id="{{ $camera->id }}" data-status-url="{{ route('cameras.stream-status', $camera) }}">
                        <td>{{ $camera->name }}</td>
                        <td>{{ $camera->ip_address }}</td>
                        <td>{{ $camera->external_ip ?: '-' }}</td>
                        <td>{{ $camera->port }}</td>
                        <td>{{ $camera->rtsp_path ?? 'stream' }}</td>
                        <td>
                            <span class="badge text-bg-secondary" data-stream-status>Stopped</span>
                        </td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                data-start-stream
                                data-start-url="{{ route('cameras.start-stream', $camera) }}"
                            >
                                Start Stream
                            </button>
                            <a href="{{ route('cameras.show', $camera) }}" class="btn btn-sm btn-success">View Stream</a>
                            <a href="{{ route('cameras.edit', $camera) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('cameras.destroy', $camera) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this camera?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No cameras configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = @json(csrf_token());

    function applyStatusBadge(badge, status) {
        const value = (status || 'stopped').toLowerCase();
        badge.classList.remove('text-bg-secondary', 'text-bg-warning', 'text-bg-success');

        if (value === 'live') {
            badge.classList.add('text-bg-success');
            badge.textContent = 'Live';
            return;
        }

        if (value === 'starting') {
            badge.classList.add('text-bg-warning');
            badge.textContent = 'Starting...';
            return;
        }

        badge.classList.add('text-bg-secondary');
        badge.textContent = 'Stopped';
    }

    async function refreshStatusForRow(row) {
        const statusUrl = row.dataset.statusUrl;
        const badge = row.querySelector('[data-stream-status]');

        try {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error('Status request failed');
            }

            const data = await response.json();
            applyStatusBadge(badge, data.status);
        } catch (error) {
            applyStatusBadge(badge, 'stopped');
        }
    }

    async function startStream(button) {
        const row = button.closest('tr');
        const badge = row.querySelector('[data-stream-status]');

        button.disabled = true;
        applyStatusBadge(badge, 'starting');

        try {
            const response = await fetch(button.dataset.startUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                throw new Error('Start request failed');
            }

            const data = await response.json();
            applyStatusBadge(badge, data.stream_status || data.status);
        } catch (error) {
            applyStatusBadge(badge, 'stopped');
        } finally {
            button.disabled = false;
        }
    }

    document.querySelectorAll('[data-start-stream]').forEach((button) => {
        button.addEventListener('click', () => startStream(button));
    });

    const rows = Array.from(document.querySelectorAll('tr[data-camera-id]'));
    rows.forEach(refreshStatusForRow);
    setInterval(() => rows.forEach(refreshStatusForRow), 5000);
</script>
@endpush
