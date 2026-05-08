<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Infrastructure\Persistence\Eloquent;

use Core\BoundedContext\Habits\Domain\Criteria\HabitsCriteria;
use Illuminate\Database\Eloquent\Builder;

/**
 * Traduce un HabitsCriteria (lenguaje de dominio) a un Eloquent Builder
 * (lenguaje de Infrastructure). Es el único punto donde se conoce qué
 * columnas existen en la tabla `habits` y cómo se filtran.
 *
 * Vive aquí, separado del repositorio, para:
 *  - mantener el repo enfocado en CRUD por id;
 *  - poder testear traducción de criterios independientemente de la
 *    persistencia;
 *  - reusar el traductor si más adelante hay otra fuente de listados
 *    (ej: una read model en otra tabla).
 */
final readonly class HabitsCriteriaTranslator
{
    /**
     * @param  Builder<\App\Models\Habit>  $query
     * @return Builder<\App\Models\Habit>
     */
    public function translate(Builder $query, HabitsCriteria $criteria): Builder
    {
        $query->where('user_id', $criteria->userId->value());

        if ($criteria->search !== null) {
            $term = $criteria->search;
            $query->where(static function (Builder $q) use ($term): void {
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            });
        }

        if ($criteria->nature !== null) {
            $query->where('habit_nature', $criteria->nature->value());
        }

        if ($criteria->desireType !== null) {
            $query->where('desire_type', $criteria->desireType->value());
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        $query->orderBy($criteria->sort->field, $criteria->sort->direction);

        return $query;
    }
}
