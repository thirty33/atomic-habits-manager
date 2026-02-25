<?php

namespace App\Listeners;

use App\Ai\Contracts\HasUserId;
use App\Models\AiInvocationLog;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolResult;

class LogAiInvocationListener
{
    public function handle(AgentPrompted $event): void
    {
        $agent = $event->prompt->agent;
        $response = $event->response;

        AiInvocationLog::create([
            'user_id' => $agent instanceof HasUserId ? $agent->userId() : null,
            'agent' => class_basename($agent),
            'prompt' => $event->prompt->prompt,
            'response' => $response->text ?: null,
            'tool_calls' => $this->formatToolCalls($response),
            'prompt_tokens' => $response->usage->promptTokens ?? null,
            'completion_tokens' => $response->usage->completionTokens ?? null,
        ]);
    }

    private function formatToolCalls(AgentResponse $response): ?string
    {
        if ($response->toolResults->isEmpty()) {
            return null;
        }

        $lines = $response->toolResults->map(function (ToolResult $result): string {
            $args = collect($result->arguments)
                ->map(fn ($v, $k) => $k.'='.json_encode($v))
                ->join(', ');

            $resultStr = is_string($result->result)
                ? $result->result
                : json_encode($result->result);

            $shortResult = mb_substr((string) $resultStr, 0, 120);

            return "{$result->name}({$args}) → \"{$shortResult}\"";
        });

        return $lines->join("\n");
    }
}
