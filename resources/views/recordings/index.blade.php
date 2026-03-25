@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Recordings</h1>
<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Camera</th>
                    <th>File Path</th>
                    <th>Duration</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recordings as $recording)
                    <tr>
                        <td>{{ $recording->camera?->name }}</td>
                        <td>{{ $recording->file_path }}</td>
                        <td>{{ $recording->duration }} sec</td>
                        <td>{{ optional($recording->created_at)->toDateTimeString() }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-success" href="{{ route('recordings.show', $recording) }}">View</a>
                            <form class="d-inline" method="POST" action="{{ route('recordings.destroy', $recording) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete recording?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4">No recordings available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $recordings->links() }}</div>
@endsection
