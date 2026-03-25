@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Create RTSP Scheme</h1>
<div class="card p-3">
    <form action="{{ route('rtsp-schemes.store') }}" method="POST">
        @include('rtsp-schemes._form')
    </form>
</div>
@endsection
