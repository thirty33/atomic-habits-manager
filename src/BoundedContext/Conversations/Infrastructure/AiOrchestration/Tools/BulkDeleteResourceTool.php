<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\BulkDeletableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class BulkDeleteResourceTool implements Tool
{
    public function __construct(
        private readonly int $userId,
        private readonly BulkDeletableResource $strategy,
    ) {}

    public function description(): Stringable|string
    {
        return "Elimina MÚLTIPLES recursos de tipo '{$this->strategy->resourceName()}' en una sola operación.\n"
            ."{$this->strategy->resourceDescription()}\n"
            .'Úsala cuando necesites eliminar más de un registro. IMPORTANTE: pide confirmación explícita al usuario antes de llamar esta herramienta.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()->enum([$this->strategy->resourceName()])
                ->description('Tipo de recurso a eliminar.')->required(),
            'ids' => $schema->array()->items($schema->integer())
                ->description('Lista de IDs de los registros a eliminar.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $ids = array_map('intval', $request['ids']);

        return $this->strategy->bulkDelete($this->userId, $ids);
    }
}
