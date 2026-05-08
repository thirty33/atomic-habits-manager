<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Tools;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\DeletableResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class DeleteResourceTool implements Tool
{
    public function __construct(
        private readonly int $userId,
        private readonly DeletableResource $strategy,
    ) {}

    public function description(): Stringable|string
    {
        return "Elimina un recurso de tipo '{$this->strategy->resourceName()}' o uno de sus elementos relacionados.\n"
            ."{$this->strategy->resourceDescription()}\n"
            .'IMPORTANTE: pide confirmación explícita al usuario antes de llamar esta herramienta.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()->enum([$this->strategy->resourceName()])
                ->description('Tipo de recurso a eliminar.')->required(),
            'id' => $schema->integer()->description('ID del recurso principal a eliminar.')->required(),
            'schedule_id' => $schema->integer()->description('ID de la programación a eliminar. Si se omite, se elimina el recurso completo.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $data = [];
        if (isset($request['schedule_id'])) {
            $data['schedule_id'] = (int) $request['schedule_id'];
        }

        return $this->strategy->delete($this->userId, (int) $request['id'], $data);
    }
}
