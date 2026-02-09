<?php

namespace App\ViewModels\Backoffice\Habits;

use App\Constants\Heroicons;
use App\Enums\DesireType;
use App\Enums\Filters\HabitFilters;
use App\Enums\HabitNature;
use App\Enums\RecurrenceType;
use App\Filters\FilterValue;
use App\Http\Resources\HabitResource;
use App\Models\Habit;
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
use App\Services\Frontend\UIElements\ColumnItems\ScheduleCompoundColumn;
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
use App\Services\Frontend\UIElements\Modals\Modal;
use App\Services\Frontend\UIElements\Modals\ModalStep;
use App\Services\Frontend\UIElements\ResourceDetailLine;
use App\Services\ViewModels\FilterService;
use App\Traits\ViewModels\WithPerPage;
use App\ViewModels\Contracts\Datatable;
use App\ViewModels\ViewModel;
use Exception;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pipeline\Pipeline;

final class GetHabitsViewModel extends ViewModel implements Datatable
{
    use WithPerPage;

    const PER_PAGE = 10;

    const ROUTE_BACKOFFICE_HABITS_STORE = 'backoffice.habits.store';

    const ROUTE_BACKOFFICE_HABIT_SCHEDULES_STORE = 'backoffice.habit-schedules.store';

    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly TableGenerator $tableGenerator,
        private readonly FilterService $filterService,
        private readonly ButtonGenerator $buttonGenerator,
        private readonly FormActionGenerator $formActionGenerator,
        private readonly ModalGenerator $modalGenerator,
        private readonly ResourceDetailGenerator $resourceDetailGenerator,
        public readonly bool $paginated = true,
    ) {
        $this->tableGenerator->initSorter(
            request(
                key: 'sorter',
                default: [
                    'column' => 'created_at',
                    'direction' => 'desc',
                ]
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
                new ScheduleCompoundColumn(
                    label: __('Programación'),
                    key: 'active_schedule',
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
                        new ActionColumn(
                            label: 'Ver',
                            class: ButtonGenerator::SHOW_CSS_CLASS,
                            event: 'show',
                        ),
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

    protected function tableFilters(): array
    {
        return array_merge(
            $this->filterService->generateSorterFilter(key: 'sorter'),
            $this->filterService->generateNormalFilter(key: 'query'),
            $this->filterService->generateNormalFilter(key: 'habit_nature'),
            $this->filterService->generateNormalFilter(key: 'desire_type'),
            $this->filterService->generateNormalFilter(key: 'is_active'),
        );
    }

    public function tableData(): ResourceCollection|LengthAwarePaginator
    {
        $models = $this->pipeline
            ->send(Habit::query()->where('user_id', auth()->id())->with('schedules'))
            ->through(
                collect($this->tableFilters())
                    ->map(fn ($filter, $value) => HabitFilters::from($value)->create(filter: new FilterValue($filter)))
                    ->values()
                    ->all()
            )->thenReturn();

        if ($this->paginated) {
            return HabitResource::collection($models->paginate($this->perPage(self::PER_PAGE)))->resource;
        }

        return HabitResource::collection($models->get());
    }

    public function tableButtons(): array
    {
        return $this->buttonGenerator
            ->addButton(
                new Button(
                    label: 'Crear habito',
                    action: 'create',
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
                    new Modal(
                        type: ModalGenerator::MODAL_DELETE,
                        title: 'Eliminar habito',
                        textSubmitButton: 'Eliminar',
                        questionMessage: 'Estas seguro de que quieres eliminar este habito?',
                        textCancelButton: 'Cancelar',
                    )
                )->getModals();
        } catch (Exception $exception) {
            \Log::error('Error al generar los modales de habitos: '.$exception->getMessage());

            return [];
        }
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
