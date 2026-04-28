@extends('layouts.app')
@section('title', 'Edit department')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">Edit department</h1>
    <div class="max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('departments.update', $department) }}">
            @method('PUT')
            @include('departments._form', ['submitLabel' => 'Save changes'])
        </form>
    </div>
@endsection
