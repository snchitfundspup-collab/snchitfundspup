{{-- Success / error messages at the top of a Traders page. --}}

@if (session('success'))
    <div class="group-flash group-flash-success" role="status">
        <x-icon name="check" />
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="group-flash group-flash-error" role="alert">
        <x-icon name="info" />
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="group-flash group-flash-error" role="alert">
        <x-icon name="info" />
        {{ $errors->first() }}
    </div>
@endif
