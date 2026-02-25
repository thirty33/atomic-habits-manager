<?php

namespace App\Ai\Tools;

use App\Ai\Contracts\CreatableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateResourceTool implements Tool
{
    public function __construct(private CreatableResource $strategy) {}

    public function description(): Stringable|string
    {
        $required = implode(', ', $this->strategy->requiredFields());

        return "Crea un nuevo recurso de tipo '{$this->strategy->resourceName()}'.\n"
            ."{$this->strategy->resourceDescription()}\n"
            ."Campos obligatorios: {$required}.";
    }

    public function schema(JsonSchema $schema): array
    {
        return array_merge(
            [
                'resource' => $schema->string()
                    ->enum([$this->strategy->resourceName()])
                    ->description('Tipo de recurso a crear.')
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

        return $this->strategy->create(auth()->id(), $data);
    }
}
