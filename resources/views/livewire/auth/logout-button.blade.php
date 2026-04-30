<button type="button"
        x-data
        x-on:click="
            Swal.fire({
                title: 'Log out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0f172a',
                confirmButtonText: 'Yes, log out',
            }).then(result => { if (result.isConfirmed) $wire.logout(); });
        "
        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
    Log out
</button>
