@props(['title' => 'Please correct the following'])
@if ($errors->any())
    <div class="alert alert-danger mb-6" role="alert" tabindex="-1" data-error-summary>
        <p class="font-semibold">{{ $title }}:</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
