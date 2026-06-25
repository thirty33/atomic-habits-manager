<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Module\Events;

use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleCode;
use Core\BoundedContext\Access\Domain\Module\ValueObjects\ModuleId;
use Core\Shared\Domain\Events\DomainEvent;
use DateTimeImmutable;

final class ModuleWasCreated extends DomainEvent
{
    public function __construct(
        public readonly ModuleId $moduleId,
        public readonly ModuleCode $code,
        ?DateTimeImmutable $occurredAt = null,
        ?string $eventId = null,
    ) {
        parent::__construct(
            occurredAt: $occurredAt ?? new DateTimeImmutable,
            eventId: $eventId ?? bin2hex(random_bytes(16)),
        );
    }

    public static function now(ModuleId $moduleId, ModuleCode $code): self
    {
        return new self(moduleId: $moduleId, code: $code);
    }

    public static function eventName(): string
    {
        return 'access.module_was_created';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'module_id' => $this->moduleId->value(),
            'code' => $this->code->value(),
        ];
    }

    /**
     * @param  array{module_id: int, code: string}  $primitives
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            moduleId: ModuleId::from((int) $primitives['module_id']),
            code: ModuleCode::from($primitives['code']),
        );
    }
}
