@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Edit Camera</h1>
<div class="card p-3">
    <form action="{{ route('cameras.update', $camera) }}" method="POST">
        @method('PUT')
        @include('cameras._form')
    </form>
</div>
@endsection
