<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Domain;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Insights\Domain\ValueObjects\InsightId;
use DateTimeImmutable;

/**
 * A persisted, LLM-generated suggestion shown on the user's dashboard. The
 * aggregate is intentionally thin: insights are derived artifacts (regenerated
 * by a daily job), not user-edited state.
 */
final class Insight
{
    private function __construct(
        private ?InsightId $id,
        private readonly UserId $userId,
        private readonly string $message,
        private readonly DateTimeImmutable $generatedAt,
    ) {}

    public static function generate(UserId $userId, string $message, DateTimeImmutable $generatedAt): self
    {
        return new self(null, $userId, $message, $generatedAt);
    }

    public static function reconstitute(InsightId $id, UserId $userId, string $message, DateTimeImmutable $generatedAt): self
    {
        return new self($id, $userId, $message, $generatedAt);
    }

    public function assignId(InsightId $id): void
    {
        $this->id = $id;
    }

    public function id(): ?InsightId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }
}
