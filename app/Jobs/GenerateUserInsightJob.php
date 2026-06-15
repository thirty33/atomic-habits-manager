<?php

namespace App\Jobs;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Insights\Application\Actions\GenerateUserInsight;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Generates and persists one LLM-backed dashboard insight for a single user.
 * Runs on the "heavy" queue because it makes an external LLM call.
 */
class GenerateUserInsightJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(public int $userId)
    {
        $this->onQueue('heavy');
    }

    public function handle(GenerateUserInsight $generate): void
    {
        $generate(UserId::from($this->userId));
    }
}
