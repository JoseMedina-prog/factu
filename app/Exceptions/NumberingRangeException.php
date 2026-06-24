<?php

namespace App\Exceptions;

use RuntimeException;

class NumberingRangeException extends RuntimeException
{
    public static function noRangeAvailable(string $documentType, string $tenantName): self
    {
        return new self(
            "No hay rangos de numeración activos disponibles para {$documentType} en el tenant {$tenantName}. "
            . "Configure un rango en Configuración > Numeración."
        );
    }

    public static function rangeExpired(string $prefix): self
    {
        return new self(
            "El rango de numeración {$prefix} ha expirado. Configure uno nuevo."
        );
    }

    public static function rangeExhausted(string $prefix): self
    {
        return new self(
            "El rango de numeración {$prefix} se ha agotado. Configure un nuevo rango antes de continuar."
        );
    }

    public static function rangeOverlaps(string $prefix): self
    {
        return new self(
            "El rango {$prefix} se superpone con otro rango existente."
        );
    }
}