@extends('layouts.app')

@section('content')
<h1 class="h3 mb-3">Create Camera</h1>
<div class="card p-3">
    <form action="{{ route('cameras.store') }}" method="POST">
        @include('cameras._form')
    </form>
</div>
@endsection
