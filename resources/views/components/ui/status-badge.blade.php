{{-- Situação da conversão de uma despesa: <x-ui.status-badge :status="$expense->conversion_status" /> --}}
@props(['status'])

@php
    $status = $status instanceof App\Enums\ConversionStatus ? $status : App\Enums\ConversionStatus::from($status);

    $variant = match ($status) {
        App\Enums\ConversionStatus::Converted => 'success',
        App\Enums\ConversionStatus::Pending => 'warning',
        App\Enums\ConversionStatus::Failed => 'danger',
    };
@endphp

<x-ui.badge :variant="$variant" dot {{ $attributes }}>{{ $status->label() }}</x-ui.badge>
