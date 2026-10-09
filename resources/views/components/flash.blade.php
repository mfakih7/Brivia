@if (session('status'))
    <div class="alert alert-success mb-6" role="status">{{ session('status') }}</div>
@endif
@if (session('warning'))
    <div class="alert alert-warning mb-6" role="status">{{ session('warning') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger mb-6" role="alert">{{ session('error') }}</div>
@endif
