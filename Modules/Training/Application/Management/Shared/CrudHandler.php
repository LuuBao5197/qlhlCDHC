<?php

namespace Modules\Training\Application\Management\Shared;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class CrudHandler
{
    abstract protected function modelClass(): string;

    abstract protected function rules(Request $request, ?int $id = null): array;

    protected function relationships(): array
    {
        return [];
    }

    protected function searchColumns(): array
    {
        return [];
    }

    /**
     * Columns that may be filtered via exact-match query params, e.g. ?status=active.
     */
    protected function filterableColumns(): array
    {
        return [];
    }

    protected function indexOrderBy(): string
    {
        return 'id';
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->newQuery();

        if ($request->filled('q') && $this->searchColumns() !== []) {
            $term = trim((string) $request->string('q'));
            $query->where(function (Builder $builder) use ($term): void {
                foreach ($this->searchColumns() as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, 'like', '%' . $term . '%');
                }
            });
        }

        foreach ($this->filterableColumns() as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->input($column));
            }
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return response()->json(
            $query->orderBy($this->indexOrderBy())->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->modelClass();
        $model = $modelClass::create($request->validate($this->rules($request)));

        return response()->json($this->freshModel($model), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->findModel($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->findModel($id);
        $model->update($request->validate($this->rules($request, $id)));

        return response()->json($this->freshModel($model));
    }

    public function destroy(int $id): JsonResponse
    {
        $model = $this->findModel($id);
        $model->delete();

        return response()->json(['message' => 'Deleted successfully.']);
    }

    protected function newQuery(): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->modelClass();

        return $modelClass::query()->with($this->relationships());
    }

    protected function findModel(int $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    protected function freshModel(Model $model): Model
    {
        return $model->fresh($this->relationships()) ?? $model;
    }
}