@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Recording: {{ $recording->camera?->name }}</h1>
<div class="card p-3">
    <video controls class="w-100" style="max-height: 70vh;" src="{{ $recordingUrl }}"></video>
</div>
@endsection
