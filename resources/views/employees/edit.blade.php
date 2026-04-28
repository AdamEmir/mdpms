@extends('layouts.app')
@section('title', 'Edit employee')

@section('content')
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">Edit employee</h1>
    <div class="max-w-2xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @method('PUT')
            @include('employees._form', ['submitLabel' => 'Save changes'])
        </form>
    </div>
@endsection
