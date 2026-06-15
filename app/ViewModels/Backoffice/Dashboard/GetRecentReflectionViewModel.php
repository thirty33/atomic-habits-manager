<?php

namespace App\ViewModels\Backoffice\Dashboard;

use App\Enums\Mood;
use App\ViewModels\ViewModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\DailyReportReader;
use Core\BoundedContext\DailyReports\Application\ReadModels\ReflectionSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * "Reflexión más reciente" card, fed by the user's latest daily report.
 */
class GetRecentReflectionViewModel extends ViewModel
{
    private const array MOOD_TONES = [
        'great' => 'success',
        'good' => 'success',
        'neutral' => 'neutral',
        'bad' => 'warning',
        'terrible' => 'danger',
    ];

    private bool $loaded = false;

    private ?ReflectionSnapshot $cachedReflection = null;

    public function __construct(private readonly DailyReportReader $reports) {}

    public function eyebrow(): string
    {
        return 'Reflexión más reciente';
    }

    /**
     * @return array{label: string, tone: string}
     */
    public function mood(): array
    {
        $mood = $this->reflection()?->mood;
        if ($mood === null) {
            return ['label' => '—', 'tone' => 'neutral'];
        }

        return [
            'label' => Mood::tryFrom($mood)?->label() ?? $mood,
            'tone' => self::MOOD_TONES[$mood] ?? 'neutral',
        ];
    }

    public function text(): string
    {
        $reflection = $this->reflection();
        if ($reflection === null || $reflection->notes === null || $reflection->notes === '') {
            return 'Aún no has escrito reflexiones en tus reportes diarios.';
        }

        return $reflection->notes;
    }

    public function datetime(): string
    {
        $reflection = $this->reflection();
        if ($reflection === null) {
            return '';
        }

        $date = Carbon::parse($reflection->reportDate)->locale('es')->isoFormat('D MMM YYYY');

        if ($reflection->createdAt === '') {
            return $date;
        }

        return $date.' · '.Carbon::parse($reflection->createdAt)->format('H:i');
    }

    public function linkLabel(): string
    {
        return 'Ver reporte completo →';
    }

    public function reportLink(): ?string
    {
        $reflection = $this->reflection();
        if ($reflection === null) {
            return null;
        }

        return route('backoffice.daily-reports.edit', $reflection->dailyReportId);
    }

    private function reflection(): ?ReflectionSnapshot
    {
        if (! $this->loaded) {
            $this->cachedReflection = $this->reports->latestReflection(UserId::from((int) auth()->id()));
            $this->loaded = true;
        }

        return $this->cachedReflection;
    }
}
