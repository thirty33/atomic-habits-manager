<?php

declare(strict_types=1);

namespace App\ViewModels\Backoffice\Users;

use App\Http\Resources\UserResource;
use App\Overrides\LengthAwarePaginator;
use App\Services\Frontend\ButtonGenerator;
use App\Services\Frontend\FormFieldsGenerator;
use App\Services\Frontend\ModalGenerator;
use App\Services\Frontend\TableGenerator;
use App\Services\Frontend\UIElements\ColumnItems\ActionColumn;
use App\Services\Frontend\UIElements\ColumnItems\ActionsColumn;
use App\Services\Frontend\UIElements\ColumnItems\BooleanColumn;
use App\Services\Frontend\UIElements\ColumnItems\DateColumn;
use App\Services\Frontend\UIElements\ColumnItems\TextColumn;
use App\Services\Frontend\UIElements\FormFields\SearchField;
use App\Services\Frontend\UIElements\FormFields\SelectField;
use App\Services\Frontend\UIElements\FormFields\SelectOptions\BooleanOption;
use App\Services\Frontend\UIElements\Modals\Modal;
use App\Traits\ViewModels\WithPerPage;
use App\ViewModels\Contracts\Datatable;
use App\ViewModels\ViewModel;
use Core\BoundedContext\Identity\Application\Actions\ListUsers;
use Core\BoundedContext\Identity\Application\DTOs\ListUsersData;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Subscriptions\Application\Payment\PaymentReader;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * ViewModel for the backoffice Users listing. Mirrors GetHabitsViewModel: builds
 * ListUsersData from the request, delegates to the ListUsers use case, then
 * composes each user's plan tier and notified-payment flag from the Subscriptions
 * read ports. The cross-BC reads are batched: ONE PlanCatalogReader::tiersOf and
 * ONE PaymentReader::usersWithNotifiedPayment per page (not 2×N), then mapped per
 * row before rendering via UserResource. Only public methods are reflected to the
 * JSON contract.
 */
final class GetUsersViewModel extends ViewModel implements Datatable
{
    use WithPerPage;

    public const PER_PAGE = 10;

    public function __construct(
        private readonly TableGenerator $tableGenerator,
        private readonly ButtonGenerator $buttonGenerator,
        private readonly ModalGenerator $modalGenerator,
        private readonly ListUsers $listUsers,
        private readonly PlanCatalogReader $planReader,
        private readonly PaymentReader $paymentReader,
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
        return __('Usuarios');
    }

    public function textModel(): string
    {
        return __('usuario');
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
                    label: __('Correo'),
                    key: 'email',
                    sortable: true,
                    direction: $this->tableGenerator->getSortDirection(column: 'email'),
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
                new BooleanColumn(
                    label: __('Correo verificado'),
                    key: 'is_verified',
                    trueValue: 'Si',
                    falseValue: 'No',
                )
            )->addColumn(
                new TextColumn(
                    label: __('Plan'),
                    key: 'plan_tier_label',
                )
            )->addColumn(
                new BooleanColumn(
                    label: __('Pago notificado'),
                    key: 'has_notified_payment',
                    trueValue: 'Si',
                    falseValue: 'No',
                )
            )->addColumn(
                new DateColumn(
                    label: __('Fecha de registro'),
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
                            label: 'Cambiar estado',
                            class: ButtonGenerator::EDIT_CSS_CLASS,
                            event: 'remove',
                        ),
                    ]
                )
            )
            ->getColumns();
    }

    public function tableData(): LengthAwarePaginator
    {
        $data = ListUsersData::fromArray([
            'query' => request('query'),
            'is_active' => request('is_active'),
            'sort_field' => request('sorter.column', 'created_at'),
            'sort_direction' => request('sorter.direction', 'desc'),
            'page' => (int) request('page', 1),
            'per_page' => $this->perPage(self::PER_PAGE),
        ]);

        $paginated = ($this->listUsers)($data);
        $rendered = $this->renderUsers($paginated->data);

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
     * @param  list<UserResponse>  $users
     * @return list<array<string, mixed>>
     */
    private function renderUsers(array $users): array
    {
        $userIds = array_map(static fn (UserResponse $user): int => $user->userId, $users);

        $tiersByUser = $this->planReader->tiersOf($userIds);
        $notifiedUserIds = array_flip($this->paymentReader->usersWithNotifiedPayment($userIds));

        return array_map(
            function (UserResponse $user) use ($tiersByUser, $notifiedUserIds): array {
                return (new UserResource(
                    $user,
                    $tiersByUser[$user->userId] ?? PlanTier::FREE,
                    isset($notifiedUserIds[$user->userId]),
                ))->resolve();
            },
            $users,
        );
    }

    public function tableButtons(): array
    {
        return $this->buttonGenerator->getButtons();
    }

    public function modals(): array
    {
        return $this->modalGenerator
            ->addModals(
                new Modal(
                    type: ModalGenerator::MODAL_DELETE,
                    title: __('Cambiar estado del usuario'),
                    textSubmitButton: __('Confirmar'),
                    questionMessage: __('¿Seguro que quieres cambiar el estado de activación de este usuario?'),
                    textCancelButton: __('Cancelar'),
                ),
            )->getModals();
    }

    public function filterFields(): array
    {
        return app(FormFieldsGenerator::class)
            ->addField(
                new SearchField(
                    name: 'query',
                    label: 'Buscador',
                    placeholder: 'Buscar por nombre o correo',
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
