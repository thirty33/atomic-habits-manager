<?php

declare(strict_types=1);

namespace Core\Shared\Domain;

/**
 * Contrato para excepciones de dominio que representan un fallo de validación
 * atribuible a uno o más campos de entrada.
 *
 * El borde HTTP (un único render en bootstrap/app.php) mapea CUALQUIER excepción
 * que implemente este contrato a una respuesta 422 con la forma
 * `{ message, errors: { campo: [..] } }` (o `back()->withErrors()` en web). Así,
 * añadir una nueva validación de dominio solo requiere implementar esta interfaz,
 * sin tocar el mapeo HTTP.
 *
 * Extiende \Throwable (núcleo de PHP, no del framework) porque solo las
 * excepciones lo implementan: así el render dispone tanto de validationErrors()
 * como de getMessage().
 */
interface ProvidesValidationErrors extends \Throwable
{
    /**
     * Errores por campo, listos para mostrar (campo => lista de mensajes).
     *
     * @return array<string, list<string>>
     */
    public function validationErrors(): array;
}
