<?php

namespace {{Namespace}}\Repositories;

use {{Namespace}}\Models\{{ModuleName}};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class {{ModuleName}}Repository
{
    protected {{ModuleName}} $model;

    public function __construct({{ModuleName}} $model)
    {
        $this->model = $model;
    }

    /**
     * Get all {{module_name}}s.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get all active {{module_name}}s.
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get all published {{module_name}}s.
     */
    public function getPublished(): Collection
    {
        return $this->model->published()->get();
    }

    /**
     * Get {{module_name}} by ID.
     */
    public function findById(int $id): ?{{ModuleName}}
    {
        return $this->model->find($id);
    }

    /**
     * Get {{module_name}} by slug.
     */
    public function findBySlug(string $slug): ?{{ModuleName}}
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Create a new {{module_name}}.
     */
    public function create(array $data): {{ModuleName}}
    {
        return $this->model->create($data);
    }

    /**
     * Update {{module_name}} by ID.
     */
    public function update(int $id, array $data): bool
    {
        ${{module_name}} = $this->findById($id);
        
        if (!${{module_name}}) {
            return false;
        }

        return ${{module_name}}->update($data);
    }

    /**
     * Delete {{module_name}} by ID.
     */
    public function delete(int $id): bool
    {
        ${{module_name}} = $this->findById($id);
        
        if (!${{module_name}}) {
            return false;
        }

        return ${{module_name}}->delete();
    }

    /**
     * Restore deleted {{module_name}} by ID.
     */
    public function restore(int $id): bool
    {
        ${{module_name}} = $this->model->withTrashed()->find($id);
        
        if (!${{module_name}}) {
            return false;
        }

        return ${{module_name}}->restore();
    }

    /**
     * Force delete {{module_name}} by ID.
     */
    public function forceDelete(int $id): bool
    {
        ${{module_name}} = $this->model->withTrashed()->find($id);
        
        if (!${{module_name}}) {
            return false;
        }

        return ${{module_name}}->forceDelete();
    }

    /**
     * Paginate {{module_name}}s.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Search {{module_name}}s by name or description.
     */
    public function search(string $query): Collection
    {
        return $this->model->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })->get();
    }

    /**
     * Get {{module_name}}s by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get recent {{module_name}}s.
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->latest()->limit($limit)->get();
    }

    /**
     * Get {{module_name}} count by status.
     */
    public function getCountByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Get total {{module_name}}s count.
     */
    public function getTotalCount(): int
    {
        return $this->model->count();
    }
}
