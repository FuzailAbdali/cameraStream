@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Edit RTSP Scheme</h1>
<div class="card p-3">
    <form action="{{ route('rtsp-schemes.update', $rtspScheme) }}" method="POST">
        @method('PUT')
        @include('rtsp-schemes._form')
    </form>
</div>
@endsection
