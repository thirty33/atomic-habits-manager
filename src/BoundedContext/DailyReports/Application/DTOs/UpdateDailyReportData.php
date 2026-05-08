<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\DTOs;

final readonly class UpdateDailyReportData
{
    public function __construct(
        public ?string $notes,
        public ?string $mood,
    ) {}

    /**
     * @param  array{notes?: ?string, mood?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            notes: $data['notes'] ?? null,
            mood: $data['mood'] ?? null,
        );
    }
}
