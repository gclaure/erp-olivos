@props([
    'on',
])

<div
    
    
    x-show.transition.out.opacity.duration.1500ms="shown"
    
    style="display: none"
    {{ $attributes->merge(['class' => 'text-sm']) }}>
    {{ $slot->isEmpty() ? __('Saved.') : $slot }}
</div>
