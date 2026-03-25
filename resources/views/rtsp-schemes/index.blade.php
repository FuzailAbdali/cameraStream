@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">RTSP Schemes</h1>
    <a href="{{ route('rtsp-schemes.create') }}" class="btn btn-primary">Add Scheme</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Template</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rtspSchemes as $scheme)
                    <tr>
                        <td>{{ $scheme->name }}</td>
                        <td><code>{{ $scheme->scheme_template }}</code></td>
                        <td class="text-end">
                            <a href="{{ route('rtsp-schemes.edit', $scheme) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('rtsp-schemes.destroy', $scheme) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this scheme?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-4">No RTSP schemes defined yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
