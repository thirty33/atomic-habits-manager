<?php

namespace App\Ai\Tools;

use App\Ai\Contracts\BulkUpdatableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class BulkUpdateResourceTool implements Tool
{
    public function __construct(private BulkUpdatableResource $strategy) {}

    public function description(): Stringable|string
    {
        $fields = implode(', ', $this->strategy->updatableFields());

        return "Actualiza MÚLTIPLES recursos de tipo '{$this->strategy->resourceName()}' en una sola operación.\n"
            ."{$this->strategy->resourceDescription()}\n"
            ."Úsala cuando necesites actualizar más de un registro. Cada item debe incluir 'id' y los campos a modificar: {$fields}.";
    }

    public function schema(JsonSchema $schema): array
    {
        $fields = implode(', ', $this->strategy->updatableFields());

        return [
            'resource' => $schema->string()
                ->enum([$this->strategy->resourceName()])
                ->description('Tipo de recurso a actualizar.')
                ->required(),
            'items' => $schema->array()
                ->description("Lista de objetos a actualizar. Cada objeto debe incluir \"id\" (int, requerido) y los campos a modificar: {$fields}.")
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $fieldNames = $this->strategy->fieldNames();
        $items = [];

        foreach ($request['items'] as $item) {
            if (! isset($item['id'])) {
                continue;
            }

            $data = ['id' => (int) $item['id']];
            foreach ($fieldNames as $field) {
                if (isset($item[$field])) {
                    $data[$field] = $item[$field];
                }
            }
            $items[] = $data;
        }

        return $this->strategy->bulkUpdate(auth()->id(), $items);
    }
}
