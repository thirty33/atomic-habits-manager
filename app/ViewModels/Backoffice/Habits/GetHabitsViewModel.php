<?php

declare(strict_types=1);

namespace App\ViewModels\Backoffice\Habits;

use App\Constants\Heroicons;
use App\Enums\DesireType;
use App\Enums\HabitNature;
use App\Enums\RecurrenceType;
use App\Http\Resources\HabitResource;
use App\Overrides\LengthAwarePaginator;
use App\Services\Frontend\ButtonGenerator;
use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\FormFieldsGenerator;
use App\Services\Frontend\ModalGenerator;
use App\Services\Frontend\ResourceDetailGenerator;
use App\Services\Frontend\TableGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use App\Services\Frontend\UIElements\Buttons\Button;
use App\Services\Frontend\UIElements\ColumnItems\ActionColumn;
use App\Services\Frontend\UIElements\ColumnItems\ActionsColumn;
use App\Services\Frontend\UIElements\ColumnItems\BooleanColumn;
use App\Services\Frontend\UIElements\ColumnItems\DateColumn;
use App\Services\Frontend\UIElements\ColumnItems\SchedulesCompoundColumn;
use App\Services\Frontend\UIElements\ColumnItems\TextColumn;
use App\Services\Frontend\UIElements\FormFields\CheckboxField;
use App\Services\Frontend\UIElements\FormFields\DateField;
use App\Services\Frontend\UIElements\FormFields\DaysOfWeekField;
use App\Services\Frontend\UIElements\FormFields\NumberField;
use App\Services\Frontend\UIElements\FormFields\SearchField;
use App\Services\Frontend\UIElements\FormFields\SelectField;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\BooleanOption;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\DesireTypeOption;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\HabitNatureOption;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\RecurrenceTypeOption;
use App\Services\Frontend\UIElements\FormFields\TextareaField;
use App\Services\Frontend\UIElements\FormFields\TextField;
use App\Services\Frontend\UIElements\FormFields\TimeField;
use App\Services\Frontend\UIElements\ModalNodes\FormNode;
use App\Services\Frontend\UIElements\ModalNodes\ListNode;
use App\Services\Frontend\UIElements\ModalNodes\NodeModal;
use App\Services\Frontend\UIElements\ModalNodes\NodeStep;
use App\Services\Frontend\UIElements\Modals\Modal;
use App\Services\Frontend\UIElements\Modals\ModalStep;
use App\Services\Frontend\UIElements\ResourceDetailLine;
use App\Traits\ViewModels\WithPerPage;
use App\ViewModels\Contracts\Datatable;
use App\ViewModels\ViewModel;
use Core\BoundedContext\Habits\Application\Actions\ListAllHabits;
use Core\BoundedContext\Habits\Application\Actions\ListHabits;
use Core\BoundedContext\Habits\Application\DTOs\ListHabitsData;
use Core\BoundedContext\Habits\Application\Responses\HabitResponse;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Exception;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * ViewModel del listado de Habits — versión DDD, paralela al legacy
 * `GetHabitsViewModel`.
 *
 * Responsabilidades:
 *  - Construir `ListHabitsData` desde `request()` y delegar al Use Case.
 *  - Componer cross-aggregate: pre-pedir los active schedules al puerto
 *    `HabitScheduleRepository` (un solo viaje, evita N+1).
 *  - Pasar cada `HabitResponse` + opcional `HabitScheduleSnapshot` al
 *    Resource (`HabitResourceDdd`) para Stage-2 de Two-Step View.
 *
 * Convive con el legacy hasta que `HabitController::json()` se migre.
 */
final class GetHabitsViewModel extends ViewModel implements Datatable
{
    use WithPerPage;

    public const PER_PAGE = 10;

    public const ROUTE_BACKOFFICE_HABITS_STORE = 'backoffice.habits.store';

    public const ROUTE_BACKOFFICE_HABIT_SCHEDULES_STORE = 'backoffice.habit-schedules.store';

    public function __construct(
        private readonly TableGenerator $tableGenerator,
        private readonly ButtonGenerator $buttonGenerator,
        private readonly FormActionGenerator $formActionGenerator,
        private readonly ModalGenerator $modalGenerator,
        private readonly ResourceDetailGenerator $resourceDetailGenerator,
        private readonly ListHabits $listHabits,
        private readonly ListAllHabits $listAllHabits,
        private readonly HabitScheduleReader $habitSchedules,
        public readonly bool $paginated = true,
    ) {
        $this->tableGenerator->initSorter(
            request(
                key: 'sorter',
                default: ['column' => 'created_at', 'direction' => 'desc']
            )
        );
    }

    public function title(): string
    {
        return __('Habitos');
    }

    public function textModel(): string
    {
        return __('habito');
    }

    public function tableColumns(): array
    {
        return $this->tableGenerator
            ->addColumn(
                new TextColumn(
                    label: __('Nombre'),
                    key: 'name',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'name'),
                )
            )->addColumn(
                new TextColumn(
                    label: __('Naturaleza'),
                    key: 'habit_nature_label',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'habit_nature'),
                    sortKey: 'habit_nature',
                )
            )->addColumn(
                new TextColumn(
                    label: __('Tipo de deseo'),
                    key: 'desire_type_label',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'desire_type'),
                    sortKey: 'desire_type',
                )
            )->addColumn(
                new BooleanColumn(
                    label: __('Activo'),
                    key: 'is_active',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'is_active'),
                    trueValue: 'Si',
                    falseValue: 'No',
                )
            )->addColumn(
                new SchedulesCompoundColumn(
                    label: __('Programación'),
                    key: 'schedules',
                )
            )->addColumn(
                new DateColumn(
                    label: __('Fecha de creacion'),
                    key: 'created_at_iso_format_ll',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'created_at'),
                    sortKey: 'created_at',
                )
            )->addColumn(
                new ActionsColumn(
                    label: __('Acciones'),
                    key: 'actions',
                    actions: [
                        // new ActionColumn(label: 'Ver', class: ButtonGenerator::SHOW_CSS_CLASS, event: 'show'),
                        // new ActionColumn(label: 'Editar', class: ButtonGenerator::EDIT_CSS_CLASS, event: 'edit'),
                        new ActionColumn(label: 'Detalles', class: ButtonGenerator::EDIT_CSS_CLASS, event: 'editNode'),
                        new ActionColumn(label: 'Eliminar', class: ButtonGenerator::DELETE_CSS_CLASS, event: 'remove'),
                    ]
                )
            )
            ->getColumns();
    }

    public function tableData(): ResourceCollection|LengthAwarePaginator
    {
        $userId = (int) auth()->id();

        if (! $this->paginated) {
            $habits = ($this->listAllHabits)($userId)->items;
            $rendered = $this->renderHabits($habits);

            // Reusamos LengthAwarePaginator para mantener el contrato Datatable
            // aunque no haya paginación real — total = items, perPage = items.
            return new LengthAwarePaginator(
                items: $rendered,
                total: count($rendered),
                perPage: max(1, count($rendered)),
                currentPage: 1,
            );
        }

        $data = ListHabitsData::fromArray([
            'user_id' => $userId,
            'query' => request('query'),
            'habit_nature' => request('habit_nature'),
            'desire_type' => request('desire_type'),
            'is_active' => request('is_active'),
            'sort_field' => request('sorter.column', 'created_at'),
            'sort_direction' => request('sorter.direction', 'desc'),
            'page' => (int) request('page', 1),
            'per_page' => $this->perPage(self::PER_PAGE),
        ]);

        $paginated = ($this->listHabits)($data);
        $rendered = $this->renderHabits($paginated->data);

        return new LengthAwarePaginator(
            items: $rendered,
            total: $paginated->meta['total'],
            perPage: $paginated->meta['per_page'],
            currentPage: $paginated->meta['current_page'],
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * Stage-2 render: aplica HabitResourceDdd (Transform View) sobre cada
     * HabitResponse. Pre-pide los active schedules al repositorio del BC
     * `HabitSchedules` con un solo viaje (evita N+1) y entrega un map
     * indexado por habit_id al Resource.
     *
     * @param  list<HabitResponse>  $habits
     * @return list<array<string, mixed>>
     */
    private function renderHabits(array $habits): array
    {
        if ($habits === []) {
            return [];
        }

        $habitIds = array_map(static fn (HabitResponse $h) => $h->habitId, $habits);

        $activeSchedules = $this->habitSchedules->findActiveByHabitIds($habitIds);
        $schedules = $this->habitSchedules->findAllActiveByHabitIds($habitIds);

        return array_map(
            fn (HabitResponse $h) => (
                new HabitResource(
                    $h,
                    $activeSchedules[$h->habitId] ?? null,
                    $schedules[$h->habitId] ?? [],
                )
            )->resolve(),
            $habits
        );
    }

    public function tableButtons(): array
    {
        return $this->buttonGenerator
            ->addButton(
                new Button(
                    label: 'Crear habito',
                    action: 'createNode',
                    icon: Heroicons::PLUS,
                    class: ButtonGenerator::CREATE_INLINE_CSS_CLASS,
                )
            )
            ->getButtons();
    }

    protected function formFields(): array
    {
        return app(FormFieldsGenerator::class)
            ->addField(
                (new TextField(
                    name: 'name',
                    label: 'Nombre',
                    placeholder: 'Nombre del habito',
                ))->required()->maxLength(50)
            )
            ->addField(
                (new SelectField(
                    name: 'habit_nature',
                    label: 'Que tipo de habito es?',
                    placeholder: 'Selecciona una opcion',
                    options: (new HabitNatureOption)->getOptions(),
                    defaultValue: HabitNature::BUILD->value,
                ))->colSpan(12)->xlColSpan(4)->required()
            )
            ->addField(
                (new SelectField(
                    name: 'desire_type',
                    label: 'Que tan importante es para ti?',
                    placeholder: 'Selecciona una opcion',
                    options: (new DesireTypeOption)->getOptions(),
                    defaultValue: DesireType::WANT->value,
                ))->colSpan(12)->xlColSpan(4)->required()
            )
            ->addField(
                (new TextField(
                    name: 'location',
                    label: 'Ubicacion',
                    placeholder: 'Lugar donde se realiza',
                ))->colSpan(12)->xlColSpan(4)->maxLength(30)
            )
            ->addField(
                (new TextareaField(
                    name: 'description',
                    label: 'Descripcion',
                    placeholder: 'Descripcion del habito',
                    rows: 1,
                ))->maxLength(80)
            )
            ->addField(
                (new TextareaField(
                    name: 'implementation_intention',
                    label: 'Intencion de implementacion',
                    placeholder: 'Ej: Despues de desayunar, voy a leer 10 paginas en la sala',
                    rows: 1,
                ))->maxLength(100)
            )
            ->addField(
                (new TextField(
                    name: 'cue',
                    label: 'Señal',
                    placeholder: 'Señal que dispara el hábito',
                ))->colSpan(12)->xlColSpan(6)->maxLength(50)
            )
            ->addField(
                (new TextareaField(
                    name: 'reframe',
                    label: 'Motivacion positiva',
                    placeholder: 'Ej: En vez de "tengo que ir al gym", piensa "voy a mejorar mi energia y salud"',
                    rows: 2,
                ))->colSpan(12)->xlColSpan(6)->maxLength(120)
            )
            ->addField(
                new CheckboxField(
                    name: 'is_active',
                    label: 'Esta activo?',
                    defaultValue: true,
                )
            )
            ->getFields();
    }

    protected function scheduleFormFields(): array
    {
        return app(FormFieldsGenerator::class)
            ->addField(
                (new SelectField(
                    name: 'recurrence_type',
                    label: '¿Con qué frecuencia?',
                    placeholder: 'Selecciona una opción',
                    options: (new RecurrenceTypeOption)->getOptions(),
                    defaultValue: RecurrenceType::DAILY->value,
                ))->colSpan(6)->xlColSpan(4)->required()
            )
            ->addField(
                (new TimeField(
                    name: 'start_time',
                    label: 'Hora de inicio',
                ))->colSpan(6)->xlColSpan(4)->required()
            )
            ->addField(
                (new TimeField(
                    name: 'end_time',
                    label: 'Hora de fin',
                ))->colSpan(6)->xlColSpan(4)->required()
            )
            ->addField(
                (new DaysOfWeekField(
                    name: 'days_of_week',
                    label: '¿Qué días?',
                ))->visibleWhen(['recurrence_type' => 'weekly'])->required()
            )
            ->addField(
                (new NumberField(
                    name: 'interval_days',
                    label: '¿Cada cuántos días?',
                    min: 1,
                ))->visibleWhen(['recurrence_type' => 'every_n_days'])->required()
            )
            ->addField(
                (new DateField(
                    name: 'specific_date',
                    label: '¿Qué día?',
                ))->visibleWhen(['recurrence_type' => 'none'])->required()
            )
            ->addField(
                (new DateField(
                    name: 'starts_from',
                    label: '¿Desde cuándo?',
                    defaultValue: now()->toDateString(),
                ))->colSpan(6)->visibleWhen(['recurrence_type' => ['daily', 'weekly', 'every_n_days']])->required()
            )
            ->addField(
                (new DateField(
                    name: 'ends_at',
                    label: '¿Hasta cuándo? (opcional)',
                ))->colSpan(6)->visibleWhen(['recurrence_type' => ['daily', 'weekly', 'every_n_days']])
            )
            ->getFields();
    }

    protected function resourceDetailSections(): array
    {
        return $this->resourceDetailGenerator
            ->addSection('Información del hábito', [
                new ResourceDetailLine(columnName: 'name', label: 'Nombre'),
                (new ResourceDetailLine(columnName: 'habit_nature_label', label: 'Tipo de hábito'))->colSpan(12)->xlColSpan(4),
                (new ResourceDetailLine(columnName: 'desire_type_label', label: 'Importancia'))->colSpan(12)->xlColSpan(4),
                (new ResourceDetailLine(columnName: 'location', label: 'Ubicación'))->colSpan(12)->xlColSpan(4),
                new ResourceDetailLine(columnName: 'description', label: 'Descripción'),
                new ResourceDetailLine(columnName: 'implementation_intention', label: 'Intención de implementación'),
                (new ResourceDetailLine(columnName: 'cue', label: 'Señal'))->colSpan(12)->xlColSpan(6),
                (new ResourceDetailLine(columnName: 'reframe', label: 'Motivación positiva'))->colSpan(12)->xlColSpan(6),
                new ResourceDetailLine(columnName: 'is_active', label: 'Activo', isBoolean: true),
            ])
            ->addSection('Programación', [
                (new ResourceDetailLine(columnName: 'recurrence_type_label', label: 'Frecuencia'))->colSpan(12)->xlColSpan(4),
                (new ResourceDetailLine(columnName: 'start_time', label: 'Hora de inicio'))->colSpan(6)->xlColSpan(4),
                (new ResourceDetailLine(columnName: 'end_time', label: 'Hora de fin'))->colSpan(6)->xlColSpan(4),
                (new ResourceDetailLine(columnName: 'days_of_week', label: 'Días de la semana')),
                (new ResourceDetailLine(columnName: 'interval_days', label: 'Cada cuántos días'))->colSpan(6),
                (new ResourceDetailLine(columnName: 'specific_date', label: 'Fecha específica'))->colSpan(6),
                (new ResourceDetailLine(columnName: 'starts_from', label: 'Desde'))->colSpan(6),
                (new ResourceDetailLine(columnName: 'ends_at', label: 'Hasta'))->colSpan(6),
            ], dataKey: 'active_schedule')
            ->getSections();
    }

    public function modals(): array
    {
        try {
            $formFields = $this->formFields();

            return $this->modalGenerator
                ->addModals(
                    /* Temporarily disabled — only "Detalles" (editNode) and delete stay active for now.
                    new Modal(
                        type: ModalGenerator::MODAL_CREATE,
                        title: 'Crear habito',
                        maxWidth: 'max-w-2xl xl:max-w-3xl 2xl:max-w-4xl',
                        steps: [
                            new ModalStep(
                                step: 1,
                                title: 'Información del hábito',
                                formFields: $formFields,
                                action: new ActionForm(
                                    url: route(self::ROUTE_BACKOFFICE_HABITS_STORE),
                                    method: FormActionGenerator::HTTP_METHOD_POST,
                                ),
                                textSubmitButton: 'Siguiente',
                            ),
                            new ModalStep(
                                step: 2,
                                title: 'Programar hábito',
                                formFields: $this->scheduleFormFields(),
                                action: new ActionForm(
                                    url: route(self::ROUTE_BACKOFFICE_HABIT_SCHEDULES_STORE),
                                    method: FormActionGenerator::HTTP_METHOD_POST,
                                ),
                                textSubmitButton: 'Guardar programación',
                                isOptional: true,
                                textSkipButton: 'Omitir por ahora',
                            ),
                        ],
                    ),
                    new Modal(
                        type: ModalGenerator::MODAL_SHOW,
                        title: 'Informacion del habito',
                        maxWidth: 'max-w-2xl xl:max-w-3xl 2xl:max-w-4xl',
                        extraData: [
                            'resource_detail_sections' => $this->resourceDetailSections(),
                        ],
                    ),
                    new Modal(
                        type: ModalGenerator::MODAL_EDIT,
                        title: 'Editar habito',
                        maxWidth: 'max-w-2xl xl:max-w-3xl 2xl:max-w-4xl',
                        steps: [
                            new ModalStep(
                                step: 1,
                                title: 'Información del hábito',
                                formFields: $formFields,
                                action: new ActionForm(
                                    url: '',
                                    method: '',
                                ),
                                textSubmitButton: 'Siguiente',
                            ),
                            new ModalStep(
                                step: 2,
                                title: 'Programar hábito',
                                formFields: $this->scheduleFormFields(),
                                action: new ActionForm(
                                    url: route(self::ROUTE_BACKOFFICE_HABIT_SCHEDULES_STORE),
                                    method: FormActionGenerator::HTTP_METHOD_POST,
                                ),
                                textSubmitButton: 'Guardar programación',
                                isOptional: true,
                                textSkipButton: 'Omitir',
                                modelDataKey: 'active_schedule',
                            ),
                        ],
                    ),
                    */
                    new Modal(
                        type: ModalGenerator::MODAL_DELETE,
                        title: 'Eliminar habito',
                        textSubmitButton: 'Eliminar',
                        questionMessage: 'Estas seguro de que quieres eliminar este habito?',
                        textCancelButton: 'Cancelar',
                    ),
                    $this->createHabitNodeModal(),
                    $this->editHabitNodeModal(),
                )->getModals();
        } catch (Exception $exception) {
            \Log::error('Error al generar los modales de habitos: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Create modal (node-based). Step 1 POSTs the habit (store route baked here, no id
     * needed); the response carries the new habit's schedules_sync_action, which the front
     * uses for step 2. Step 2 is optional (a habit may be created without schedules).
     */
    protected function createHabitNodeModal(): NodeModal
    {
        return new NodeModal(
            type: 'createNode',
            title: 'Crear hábito',
            maxWidth: 'max-w-2xl xl:max-w-3xl 2xl:max-w-4xl',
            steps: [
                new NodeStep(
                    step: 1,
                    title: 'Información del hábito',
                    action: new ActionForm(url: route(self::ROUTE_BACKOFFICE_HABITS_STORE)),
                    content: new FormNode(
                        fields: $this->formFields(),
                    ),
                    submitText: 'Guardar y continuar',
                ),
                new NodeStep(
                    step: 2,
                    title: 'Programación',
                    subtitle: 'Un hábito puede repetirse en distintos momentos.',
                    action: new ActionForm(url: '', method: ''),
                    content: new ListNode(
                        itemTemplate: new FormNode(
                            fields: $this->scheduleFormFields(),
                        ),
                        addLabel: '+ Crear otra programación',
                        minItems: 1,
                        summaryFields: ['recurrence_type', 'start_time', 'end_time'],
                        itemsKey: 'schedules',
                        idKey: 'habit_schedule_id',
                        syncActionKey: 'schedules_sync_action',
                    ),
                    submitText: 'Guardar programaciones',
                    isOptional: true,
                    skipText: 'Omitir por ahora',
                ),
            ],
        );
    }

    /**
     * Read-only node modal to verify the new component structure renders a habit
     * and its schedules. Type 'editNode' so it lands in data.modals['editNode']
     * and is mounted by modalComponents['editNode'] (AppModalSteps). The actions
     * are placeholders — nothing is submitted in this phase.
     */
    protected function editHabitNodeModal(): NodeModal
    {
        return new NodeModal(
            type: 'editNode',
            title: 'Hábito (nodos)',
            maxWidth: 'max-w-2xl xl:max-w-3xl 2xl:max-w-4xl',
            steps: [
                new NodeStep(
                    step: 1,
                    title: 'Información del hábito',
                    action: new ActionForm(url: '', method: ''),
                    content: new FormNode(
                        fields: $this->formFields(),
                    ),
                    submitText: 'Guardar y continuar',
                ),
                new NodeStep(
                    step: 2,
                    title: 'Programación',
                    subtitle: 'Un hábito puede repetirse en distintos momentos.',
                    action: new ActionForm(url: '', method: ''),
                    content: new ListNode(
                        itemTemplate: new FormNode(
                            fields: $this->scheduleFormFields(),
                        ),
                        addLabel: '+ Crear otra programación',
                        minItems: 1,
                        summaryFields: ['recurrence_type', 'start_time', 'end_time'],
                        itemsKey: 'schedules',
                        idKey: 'habit_schedule_id',
                        syncActionKey: 'schedules_sync_action',
                    ),
                    submitText: 'Guardar programaciones',
                ),
            ],
        );
    }

    public function filterFields(): array
    {
        return app(FormFieldsGenerator::class)
            ->addField(
                new SearchField(
                    name: 'query',
                    label: 'Buscador',
                    placeholder: 'Buscar',
                )
            )
            ->addField(
                new SelectField(
                    name: 'habit_nature',
                    label: 'Naturaleza del habito',
                    placeholder: 'Selecciona una opcion',
                    options: (new HabitNatureOption)->getOptions(),
                )
            )
            ->addField(
                new SelectField(
                    name: 'desire_type',
                    label: 'Tipo de deseo',
                    placeholder: 'Selecciona una opcion',
                    options: (new DesireTypeOption)->getOptions(),
                )
            )
            ->addField(
                new SelectField(
                    name: 'is_active',
                    label: 'Esta activo?',
                    placeholder: 'Selecciona una opcion',
                    options: (new BooleanOption)->getOptions(),
                )
            )
            ->getFields();
    }
}
