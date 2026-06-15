<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Infrastructure\Ai;

use Core\BoundedContext\Insights\Application\InsightTextGenerator;
use Laravel\Ai\AnonymousAgent;

/**
 * laravel/ai adapter: turns the report-analysis facts into a single suggestion
 * via a one-shot, tool-less prompt. Provider/model come from config/ai.php.
 */
final readonly class LaravelAiInsightTextGenerator implements InsightTextGenerator
{
    private const string INSTRUCTIONS = <<<'PROMPT'
    Eres Atomic IA, un asistente de hábitos atómicos basado en el libro "Atomic Habits" de James Clear.
    A partir de los datos de hábitos y reportes que te paso, redacta UNA sola sugerencia para el usuario.

    Reglas:
    - Responde SIEMPRE en español.
    - Una o dos frases como máximo, concreta, accionable y motivadora.
    - Basate en los números: refuerza lo que va bien o señala el hábito que más se le escapa.
    - No uses markdown, ni saludos, ni emojis, ni comillas alrededor de toda la frase.
    - No menciones que eres una IA, ni hables de "datos", "reportes" o detalles técnicos.
    - Devuelve únicamente la sugerencia, sin prefijos ni explicaciones.
    PROMPT;

    private const string FALLBACK = 'Seguí registrando tus hábitos cada día: en cuanto haya algo de historial te daré una sugerencia personalizada.';

    public function __construct(
        private string $provider,
        private string $model,
    ) {}

    public function generate(string $analysis): string
    {
        $agent = new AnonymousAgent(
            instructions: self::INSTRUCTIONS,
            messages: [],
            tools: [],
        );

        $response = $agent->prompt($analysis, provider: $this->provider, model: $this->model);

        $text = trim((string) $response);

        return $text !== '' ? $text : self::FALLBACK;
    }
}
