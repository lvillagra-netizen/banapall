@if (session('success'))
    <div class="alert alert-success mb-5">
        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error mb-5">
        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v5m0 3h.01M10.3 4.3 2.8 17.2A1.7 1.7 0 0 0 4.3 20h15.4a1.7 1.7 0 0 0 1.5-2.8L13.7 4.3a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <p>{{ session('error') }}</p>
    </div>
@endif

@if (session('status'))
    <div class="alert alert-info mb-5">
        <p>{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error mb-5">
        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v5m0 3h.01M10.3 4.3 2.8 17.2A1.7 1.7 0 0 0 4.3 20h15.4a1.7 1.7 0 0 0 1.5-2.8L13.7 4.3a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div>
            <p class="font-semibold">Revisa la información:</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
