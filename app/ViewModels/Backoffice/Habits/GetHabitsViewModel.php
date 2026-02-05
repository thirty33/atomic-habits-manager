<?php

namespace App\ViewModels\Backoffice\Habits;

use App\Constants\Heroicons;
use App\Enums\DesireType;
use App\Enums\HabitNature;
use App\Enums\Filters\HabitFilters;
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
use App\Services\Frontend\UIElements\ColumnItems\TextColumn;
use App\Enums\RecurrenceType;
use App\Services\Frontend\UIElements\FormFields\CheckboxField;
use App\Services\Frontend\UIElements\FormFields\DaysOfWeekField;
use App\Services\Frontend\UIElements\FormFields\DateField;
use App\Services\Frontend\UIElements\FormFields\NumberField;
use App\Services\Frontend\UIElements\FormFields\SearchField;
use App\Services\Frontend\UIElements\FormFields\SelectField;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\BooleanOption;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\DesireTypeOption;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\HabitNatureOption;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\RecurrenceTypeOption;
use App\Services\Frontend\UIElements\FormFields\TextField;
use App\Services\Frontend\UIElements\FormFields\TextareaField;
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
    )
    {
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
            ->send(Habit::query()->where('user_id', auth()->id()))
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
                new TextField(
                    name: 'name',
                    label: 'Nombre',
                    placeholder: 'Nombre del habito',
                )
            )
            ->addField(
                new TextareaField(
                    name: 'description',
                    label: 'Descripcion',
                    placeholder: 'Descripcion del habito',
                    rows: 3,
                )
            )
            ->addField(
                new SelectField(
                    name: 'habit_nature',
                    label: 'Que tipo de habito es?',
                    placeholder: 'Selecciona una opcion',
                    options: (new HabitNatureOption())->getOptions(),
                    defaultValue: HabitNature::BUILD->value,
                )
            )
            ->addField(
                new SelectField(
                    name: 'desire_type',
                    label: 'Que tan importante es para ti?',
                    placeholder: 'Selecciona una opcion',
                    options: (new DesireTypeOption())->getOptions(),
                    defaultValue: DesireType::WANT->value,
                )
            )
            ->addField(
                new TextareaField(
                    name: 'implementation_intention',
                    label: 'Intencion de implementacion',
                    placeholder: 'Ej: Despues de desayunar, voy a leer 10 paginas en la sala',
                    rows: 3,
                )
            )
            ->addField(
                new TextField(
                    name: 'location',
                    label: 'Ubicacion',
                    placeholder: 'Lugar donde se realiza',
                )
            )
            ->addField(
                new TextField(
                    name: 'cue',
                    label: 'Señal',
                    placeholder: 'Señal que dispara el hábito',
                )
            )
            ->addField(
                new TextareaField(
                    name: 'reframe',
                    label: 'Motivacion positiva',
                    placeholder: 'Ej: En vez de "tengo que ir al gym", piensa "voy a mejorar mi energia y salud"',
                    rows: 3,
                )
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
                new SelectField(
                    name: 'recurrence_type',
                    label: '¿Con qué frecuencia?',
                    placeholder: 'Selecciona una opción',
                    options: (new RecurrenceTypeOption())->getOptions(),
                    defaultValue: RecurrenceType::DAILY->value,
                )
            )
            ->addField(
                new TimeField(
                    name: 'start_time',
                    label: 'Hora de inicio',
                )
            )
            ->addField(
                new TimeField(
                    name: 'end_time',
                    label: 'Hora de fin',
                )
            )
            ->addField(
                (new DaysOfWeekField(
                    name: 'days_of_week',
                    label: '¿Qué días?',
                ))->visibleWhen(['recurrence_type' => 'weekly'])
            )
            ->addField(
                (new NumberField(
                    name: 'interval_days',
                    label: '¿Cada cuántos días?',
                    min: 1,
                ))->visibleWhen(['recurrence_type' => 'every_n_days'])
            )
            ->addField(
                (new DateField(
                    name: 'specific_date',
                    label: '¿Qué día?',
                ))->visibleWhen(['recurrence_type' => 'none'])
            )
            ->addField(
                (new DateField(
                    name: 'starts_from',
                    label: '¿Desde cuándo?',
                    defaultValue: now()->toDateString(),
                ))->visibleWhen(['recurrence_type' => ['daily', 'weekly', 'every_n_days']])
            )
            ->addField(
                (new DateField(
                    name: 'ends_at',
                    label: '¿Hasta cuándo? (opcional)',
                ))->visibleWhen(['recurrence_type' => ['daily', 'weekly', 'every_n_days']])
            )
            ->getFields();
    }

    protected function resourceDetailConfig(): array
    {
        return $this->resourceDetailGenerator
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'name',
                    label: 'Nombre',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'description',
                    label: 'Descripcion',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'habit_nature_label',
                    label: 'Que tipo de habito es?',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'desire_type_label',
                    label: 'Que tan importante es para ti?',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'implementation_intention',
                    label: 'Intencion de implementacion',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'location',
                    label: 'Ubicacion',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'cue',
                    label: 'Señal',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'reframe',
                    label: 'Motivacion positiva',
                )
            )
            ->addLine(
                new ResourceDetailLine(
                    columnName: 'is_active',
                    label: 'Esta activo?',
                    isBoolean: true,
                )
            )
            ->getLines();
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
                        extraData: [
                            'resource_detail_config' => $this->resourceDetailConfig(),
                        ],
                    ),
                    new Modal(
                        type: ModalGenerator::MODAL_EDIT,
                        title: 'Editar habito',
                        textSubmitButton: 'Editar',
                        formFields: $formFields,
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
            \Log::error('Error al generar los modales de habitos: ' . $exception->getMessage());
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
                    options: (new HabitNatureOption())->getOptions(),
                )
            )
            ->addField(
                new SelectField(
                    name: 'desire_type',
                    label: 'Tipo de deseo',
                    placeholder: 'Selecciona una opcion',
                    options: (new DesireTypeOption())->getOptions(),
                )
            )
            ->addField(
                new SelectField(
                    name: 'is_active',
                    label: 'Esta activo?',
                    placeholder: 'Selecciona una opcion',
                    options: (new BooleanOption())->getOptions(),
                )
            )
            ->getFields();
    }
}