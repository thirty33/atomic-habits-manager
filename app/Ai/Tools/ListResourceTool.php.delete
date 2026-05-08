<?php

namespace App\Ai\Tools;

use App\Ai\Contracts\ListableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListResourceTool implements Tool
{
    /** @var array<string, ListableResource> */
    private array $resources = [];

    public function __construct(ListableResource ...$resources)
    {
        foreach ($resources as $resource) {
            $this->resources[$resource->resourceName()] = $resource;
        }
    }

    public function description(): Stringable|string
    {
        $descriptions = array_map(
            fn (ListableResource $r) => "- {$r->resourceName()}: {$r->resourceDescription()}",
            $this->resources
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
            'parent_id' => $schema->integer()
                ->description('ID del recurso padre. Requerido para recursos hijos.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $resourceName = $request['resource'];

        if (! isset($this->resources[$resourceName])) {
            return "Recurso '{$resourceName}' no disponible.";
        }

        return $this->resources[$resourceName]->list(
            auth()->id(),
            isset($request['parent_id']) ? (int) $request['parent_id'] : null
        );
    }
}
