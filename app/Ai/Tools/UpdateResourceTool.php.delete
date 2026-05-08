<?php

namespace App\Ai\Tools;

use App\Ai\Contracts\UpdatableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateResourceTool implements Tool
{
    public function __construct(private UpdatableResource $strategy) {}

    public function description(): Stringable|string
    {
        $fields = implode(', ', $this->strategy->updatableFields());

        return "Actualiza un recurso existente de tipo '{$this->strategy->resourceName()}'.\n"
            ."{$this->strategy->resourceDescription()}\n"
            ."Campos actualizables: {$fields}.";
    }

    public function schema(JsonSchema $schema): array
    {
        return array_merge(
            [
                'resource' => $schema->string()
                    ->enum([$this->strategy->resourceName()])
                    ->description('Tipo de recurso a actualizar.')
                    ->required(),
                'id' => $schema->integer()
                    ->description('ID del recurso a actualizar.')
                    ->required(),
            ],
            $this->strategy->schemaFields($schema)
        );
    }

    public function handle(Request $request): Stringable|string
    {
        $data = [];

        foreach ($this->strategy->fieldNames() as $field) {
            if (isset($request[$field])) {
                $data[$field] = $request[$field];
            }
        }

        return $this->strategy->update(auth()->id(), (int) $request['id'], $data);
    }
}
