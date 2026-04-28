@extends('layouts.app')
@section('title', 'New department')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">New department</h1>
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('departments.store') }}">
            @include('departments._form', ['submitLabel' => 'Create department'])
        </form>
    </div>
@endsection
