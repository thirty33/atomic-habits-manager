<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RespondToUserTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Envía la respuesta final al usuario. DEBES llamar esta herramienta como ÚLTIMO paso de cualquier interacción, incluso después de ejecutar otras herramientas. Úsala para resumir lo que hiciste o para responder directamente al usuario.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()
                ->description('El mensaje final para mostrar al usuario. Debe ser claro, amigable y en español.')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        return 'OK';
    }
}
