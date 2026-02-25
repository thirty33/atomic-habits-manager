<?php

namespace App\Ai\Tools;

use App\Ai\Contracts\BulkCreatableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class BulkCreateResourceTool implements Tool
{
    public function __construct(private BulkCreatableResource $strategy) {}

    public function description(): Stringable|string
    {
        $required = implode(', ', $this->strategy->requiredFields());

        return "Crea MÚLTIPLES recursos de tipo '{$this->strategy->resourceName()}' en una sola operación.\n"
            ."{$this->strategy->resourceDescription()}\n"
            ."Úsala cuando necesites crear más de un registro. Campos obligatorios por item: {$required}.";
    }

    public function schema(JsonSchema $schema): array
    {
        $required = implode(', ', $this->strategy->requiredFields());
        $all = implode(', ', $this->strategy->fieldNames());

        return [
            'resource' => $schema->string()
                ->enum([$this->strategy->resourceName()])
                ->description('Tipo de recurso a crear.')
                ->required(),
            'items' => $schema->array()
                ->description("Lista de objetos a crear. Campos obligatorios por item: {$required}. Campos disponibles: {$all}.")
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $fieldNames = $this->strategy->fieldNames();
        $items = [];

        foreach ($request['items'] as $item) {
            $data = [];
            foreach ($fieldNames as $field) {
                if (isset($item[$field])) {
                    $data[$field] = $item[$field];
                }
            }
            $items[] = $data;
        }

        return $this->strategy->bulkCreate(auth()->id(), $items);
    }
}
