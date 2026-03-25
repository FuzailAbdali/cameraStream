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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cameras as $camera)
                    <tr>
                        <td>{{ $camera->name }}</td>
                        <td>{{ $camera->ip_address }}</td>
                        <td>{{ $camera->external_ip ?: '-' }}</td>
                        <td>{{ $camera->port }}</td>
                        <td class="text-end">
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
                        <td colspan="5" class="text-center py-4">No cameras configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
