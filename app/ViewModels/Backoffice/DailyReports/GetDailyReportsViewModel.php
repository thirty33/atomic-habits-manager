<?php

declare(strict_types=1);

namespace App\ViewModels\Backoffice\DailyReports;

use App\Constants\Heroicons;
use App\Http\Resources\DailyReportResource;
use App\Overrides\LengthAwarePaginator;
use App\Services\Frontend\ButtonGenerator;
use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\FormFieldsGenerator;
use App\Services\Frontend\ModalGenerator;
use App\Services\Frontend\TableGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use App\Services\Frontend\UIElements\Buttons\Button;
use App\Services\Frontend\UIElements\ColumnItems\ActionColumn;
use App\Services\Frontend\UIElements\ColumnItems\ActionsColumn;
use App\Services\Frontend\UIElements\ColumnItems\DateColumn;
use App\Services\Frontend\UIElements\ColumnItems\TextColumn;
use App\Services\Frontend\UIElements\FormFields\DateField;
use App\Services\Frontend\UIElements\FormFields\SelectField;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\MoodOption;
use App\Services\Frontend\UIElements\Modals\Modal;
use App\Services\Frontend\UIElements\Modals\ModalStep;
use App\Traits\ViewModels\WithPerPage;
use App\ViewModels\Contracts\Datatable;
use App\ViewModels\ViewModel;
use Core\BoundedContext\DailyReports\Application\Actions\GetDailyReportsForUser;
use Core\BoundedContext\DailyReports\Domain\Criteria\DailyReportsCriteria;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\Mood;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Exception;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class GetDailyReportsViewModel extends ViewModel implements Datatable
{
    use WithPerPage;

    const PER_PAGE = 15;

    const ROUTE_STORE = 'backoffice.daily-reports.store';

    public function __construct(
        private readonly TableGenerator $tableGenerator,
        private readonly ButtonGenerator $buttonGenerator,
        private readonly ModalGenerator $modalGenerator,
        private readonly GetDailyReportsForUser $useCase,
    ) {
        $this->tableGenerator->initSorter(
            request(
                key: 'sorter',
                default: [
                    'column' => 'report_date',
                    'direction' => 'desc',
                ]
            )
        );
    }

    public function title(): string
    {
        return __('Reportes diarios');
    }

    public function textModel(): string
    {
        return __('reporte');
    }

    public function tableColumns(): array
    {
        return $this->tableGenerator
            ->addColumn(
                new DateColumn(
                    label: __('Fecha'),
                    key: 'report_date',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'report_date'),
                )
            )->addColumn(
                new TextColumn(
                    label: __('Estado de ánimo'),
                    key: 'mood_label',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'mood'),
                    sortKey: 'mood',
                )
            )->addColumn(
                new TextColumn(
                    label: __('Progreso'),
                    key: 'progress_label',
                )
            )->addColumn(
                new ActionsColumn(
                    label: __('Acciones'),
                    key: 'actions',
                    actions: [
                        new ActionColumn(
                            label: 'Editar',
                            class: ButtonGenerator::EDIT_CSS_CLASS,
                            event: 'edit',
                        ),
                        new ActionColumn(
                            label: 'Eliminar',
                            class: ButtonGenerator::DELETE_CSS_CLASS,
                            event: 'remove',
                        ),
                    ]
                )
            )
            ->getColumns();
    }

    public function tableData(): ResourceCollection|LengthAwarePaginator
    {
        $userId = (int) auth()->id();

        $sorter = (array) request()->input('sorter', ['column' => 'report_date', 'direction' => 'desc']);
        $sortBy = in_array($sorter['column'] ?? '', ['report_date', 'mood'], true)
            ? $sorter['column']
            : 'report_date';
        $sortDir = ($sorter['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $criteria = new DailyReportsCriteria(
            userId: UserId::from($userId),
            fromDate: $this->fromDate(),
            toDate: $this->toDate(),
            mood: $this->mood(),
            page: (int) request()->input('page', 1),
            perPage: $this->perPage(self::PER_PAGE),
            sortBy: $sortBy,
            sortDir: $sortDir,
        );

        $page = ($this->useCase)($criteria);

        $rendered = [];
        foreach ($page->items->items() as $report) {
            $snap = \Core\BoundedContext\DailyReports\Application\Responses\DailyReportResponse::from($report)->snapshot;
            $rendered[] = (new DailyReportResource($snap))->resolve();
        }

        return new LengthAwarePaginator(
            items: $rendered,
            total: $page->total,
            perPage: $page->perPage,
            currentPage: $page->page,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    public function tableButtons(): array
    {
        return $this->buttonGenerator
            ->addButton(
                new Button(
                    label: 'Nuevo reporte',
                    action: 'create',
                    icon: Heroicons::PLUS,
                    class: ButtonGenerator::CREATE_INLINE_CSS_CLASS,
                )
            )
            ->getButtons();
    }

    public function modals(): array
    {
        try {
            return $this->modalGenerator
                ->addModals(
                    new Modal(
                        type: ModalGenerator::MODAL_CREATE,
                        title: 'Crear reporte diario',
                        maxWidth: 'max-w-md',
                        steps: [
                            new ModalStep(
                                step: 1,
                                title: 'Seleccionar fecha',
                                formFields: $this->createFormFields(),
                                action: new ActionForm(
                                    url: route(self::ROUTE_STORE),
                                    method: FormActionGenerator::HTTP_METHOD_POST,
                                ),
                                textSubmitButton: 'Crear reporte',
                            ),
                        ],
                    ),
                    new Modal(
                        type: ModalGenerator::MODAL_DELETE,
                        title: 'Eliminar reporte',
                        textSubmitButton: 'Eliminar',
                        questionMessage: '¿Estás seguro de que quieres eliminar este reporte? Se borrarán todas sus entradas.',
                        textCancelButton: 'Cancelar',
                    )
                )->getModals();
        } catch (Exception $exception) {
            \Log::error('Error al generar los modales de reportes: '.$exception->getMessage());

            return [];
        }
    }

    public function filterFields(): array
    {
        return app(FormFieldsGenerator::class)
            ->addField(
                (new DateField(
                    name: 'date_range_from',
                    label: 'Desde',
                    max: now()->toDateString(),
                ))->colSpan(6)
            )
            ->addField(
                (new DateField(
                    name: 'date_range_to',
                    label: 'Hasta',
                    max: now()->toDateString(),
                ))->colSpan(6)
            )
            ->addField(
                new SelectField(
                    name: 'mood',
                    label: 'Estado de ánimo',
                    placeholder: 'Todos',
                    options: (new MoodOption)->getOptions(),
                )
            )
            ->getFields();
    }

    private function createFormFields(): array
    {
        return app(FormFieldsGenerator::class)
            ->addField(
                (new DateField(
                    name: 'report_date',
                    label: '¿Para qué fecha?',
                    defaultValue: now()->toDateString(),
                    max: now()->toDateString(),
                ))->required()
            )
            ->getFields();
    }

    private function fromDate(): ?ReportDate
    {
        $value = request()->input('date_range_from');

        return $value !== null && $value !== '' ? ReportDate::fromString($value) : null;
    }

    private function toDate(): ?ReportDate
    {
        $value = request()->input('date_range_to');

        return $value !== null && $value !== '' ? ReportDate::fromString($value) : null;
    }

    private function mood(): ?Mood
    {
        $value = request()->input('mood');

        return $value !== null && $value !== '' ? Mood::from($value) : null;
    }
}
