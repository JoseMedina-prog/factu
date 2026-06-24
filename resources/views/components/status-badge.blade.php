@props(['status' => 'draft'])

@php
    $map = [
        'draft' => ['variant' => 'warning', 'label' => 'Borrador'],
        'pending' => ['variant' => 'info', 'label' => 'Pendiente'],
        'sent' => ['variant' => 'info', 'label' => 'Enviada'],
        'approved' => ['variant' => 'success', 'label' => 'Aprobada'],
        'rejected' => ['variant' => 'danger', 'label' => 'Rechazada'],
        'cancelled' => ['variant' => 'neutral', 'label' => 'Cancelada'],
        'error' => ['variant' => 'danger', 'label' => 'Error'],
    ];

    $config = $map[$status] ?? ['variant' => 'neutral', 'label' => ucfirst($status)];
@endphp

<x-badge :variant="$config['variant']">
    {{ $config['label'] }}
</x-badge>
