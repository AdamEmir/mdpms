@csrf
<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Department name</label>
    <input id="name" type="text" name="name" required
           value="{{ old('name', $department->name ?? '') }}"
           class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('departments.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</a>
    <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">{{ $submitLabel }}</button>
</div>
