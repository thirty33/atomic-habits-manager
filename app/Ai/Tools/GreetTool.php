<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GreetTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Genera un saludo personalizado para el usuario.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nombre del usuario a saludar.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        return "¡Hola {$request['name']}! Soy Atomic IA, tu asistente de hábitos atómicos. Estoy en desarrollo, pero pronto podré ayudarte con tus hábitos.";
    }
}
