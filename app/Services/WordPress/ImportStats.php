<?php

namespace App\Services\WordPress;

use Illuminate\Database\Eloquent\Model;

/** Created/updated/skipped counters per entity, rendered as a console table. */
class ImportStats
{
    /** @var array<string, array{created: int, updated: int, unchanged: int, skipped: int}> */
    protected array $rows = [];

    /** @var array<int, string> */
    public array $notes = [];

    public function add(string $entity, string $column, int $count = 1): static
    {
        $this->rows[$entity] ??= ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $this->rows[$entity][$column] += $count;

        return $this;
    }

    /** Count a saved model as created / updated / unchanged. */
    public function record(string $entity, Model $model): static
    {
        return $this->add($entity, match (true) {
            $model->wasRecentlyCreated => 'created',
            $model->wasChanged() => 'updated',
            default => 'unchanged',
        });
    }

    public function skip(string $entity, ?string $note = null): static
    {
        if ($note) {
            $this->notes[] = $note;
        }

        return $this->add($entity, 'skipped');
    }

    public function note(string $note): static
    {
        $this->notes[] = $note;

        return $this;
    }

    public function merge(self $other): static
    {
        foreach ($other->rows as $entity => $columns) {
            foreach ($columns as $column => $count) {
                $this->add($entity, $column, $count);
            }
        }

        $this->notes = array_merge($this->notes, $other->notes);

        return $this;
    }

    public function count(string $entity, string $column): int
    {
        return $this->rows[$entity][$column] ?? 0;
    }

    /** @return array<int, array<int, string|int>> */
    public function tableRows(): array
    {
        return collect($this->rows)->map(fn ($r, $entity) => [$entity, $r['created'], $r['updated'], $r['unchanged'], $r['skipped']])->values()->all();
    }

    /** @return array<int, string> */
    public static function headers(): array
    {
        return ['Entity', 'Created', 'Updated', 'Unchanged', 'Skipped'];
    }
}
