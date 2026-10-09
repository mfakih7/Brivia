@props(['media', 'sizes' => '100vw', 'eager' => false, 'alt' => null])
<img src="{{ $media->url(960) }}" srcset="{{ $media->srcset() }}" sizes="{{ $sizes }}"
     width="{{ $media->width }}" height="{{ $media->height }}"
     alt="{{ $alt ?? $media->alt_text ?? '' }}"
     loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if ($eager) fetchpriority="high" @endif
     {{ $attributes }}>
