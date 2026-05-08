<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Application\DTOs;

/**
 * Input para el `HandleAiResponseAction` — el único Use Case asíncrono
 * que orquesta el ciclo completo "user posteó → IA responde → moderador
 * decide → fallback si banea → broadcast a la UI".
 */
final readonly class HandleAiResponseData
{
    public function __construct(public int $conversationId) {}
}
