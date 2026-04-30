<div>
    @include('partials.flash-messages')

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Payslip</h1>
        <a href="{{ route('payroll.payslip.pdf', $record) }}"
           class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            Download PDF
        </a>
    </div>

    @include('payroll._payslip-card', ['record' => $record])

    <div class="mt-4">
        <a href="{{ route('payroll.history') }}" wire:navigate class="text-sm font-medium text-slate-600 hover:underline">&larr; Back to history</a>
    </div>
</div>
