<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\ListableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ListResourceTool implements Tool
{
    /** @var array<string, ListableResource> */
    private readonly array $resources;

    public function __construct(
        private readonly int $userId,
        ListableResource ...$resources,
    ) {
        $byName = [];
        foreach ($resources as $r) {
            $byName[$r->resourceName()] = $r;
        }
        $this->resources = $byName;
    }

    public function description(): Stringable|string
    {
        $descriptions = array_map(
            static fn (ListableResource $r) => "- {$r->resourceName()}: {$r->resourceDescription()}",
            $this->resources,
        );

        return "Lista recursos del usuario.\n\nRecursos disponibles:\n".implode("\n", $descriptions);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()
                ->enum(array_keys($this->resources))
                ->description('Tipo de recurso a listar.')
                ->required(),
            'parent_id' => $schema->integer()->description('ID del recurso padre. Requerido para recursos hijos.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $resourceName = $request['resource'];

        if (! isset($this->resources[$resourceName])) {
            return "Recurso '{$resourceName}' no disponible.";
        }

        return $this->resources[$resourceName]->list(
            $this->userId,
            isset($request['parent_id']) ? (int) $request['parent_id'] : null,
        );
    }
}
