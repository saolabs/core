<?php

namespace {{Namespace}}\Masks;

use {{Namespace}}\Models\{{ModuleName}};

class {{ModuleName}}Mask
{
    protected {{ModuleName}} $model;

    public function __construct({{ModuleName}} $model)
    {
        $this->model = $model;
    }

    /**
     * Get basic {{module_name}} data.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
            'slug' => $this->model->slug,
            'status' => $this->model->status,
            'excerpt' => $this->model->excerpt,
            'created_at' => $this->model->formatted_created_at,
            'updated_at' => $this->model->formatted_updated_at,
        ];
    }

    /**
     * Get detailed {{module_name}} data.
     */
    public function toDetailArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
            'slug' => $this->model->slug,
            'status' => $this->model->status,
            'meta_data' => $this->model->meta_data,
            'excerpt' => $this->model->excerpt,
            'created_at' => $this->model->created_at,
            'updated_at' => $this->model->updated_at,
            'formatted_created_at' => $this->model->formatted_created_at,
            'formatted_updated_at' => $this->model->formatted_updated_at,
        ];
    }

    /**
     * Get public {{module_name}} data (for API).
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
            'slug' => $this->model->slug,
            'status' => $this->model->status,
            'excerpt' => $this->model->excerpt,
            'created_at' => $this->model->formatted_created_at,
        ];
    }

    /**
     * Get {{module_name}} data for admin.
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
            'slug' => $this->model->slug,
            'status' => $this->model->status,
            'meta_data' => $this->model->meta_data,
            'excerpt' => $this->model->excerpt,
            'created_at' => $this->model->created_at,
            'updated_at' => $this->model->updated_at,
            'formatted_created_at' => $this->model->formatted_created_at,
            'formatted_updated_at' => $this->model->formatted_updated_at,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
        ];
    }

    /**
     * Get {{module_name}} data for listing.
     */
    public function toListItemArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'slug' => $this->model->slug,
            'status' => $this->model->status,
            'excerpt' => $this->model->excerpt,
            'created_at' => $this->model->formatted_created_at,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
        ];
    }

    /**
     * Get {{module_name}} data for search results.
     */
    public function toSearchResultArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
            'slug' => $this->model->slug,
            'excerpt' => $this->model->excerpt,
            'created_at' => $this->model->formatted_created_at,
        ];
    }

    /**
     * Get status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->model->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'published' => 'Published',
            'draft' => 'Draft',
            'archived' => 'Archived',
            default => 'Unknown',
        };
    }

    /**
     * Get status color.
     */
    protected function getStatusColor(): string
    {
        return match ($this->model->status) {
            'active', 'published' => 'green',
            'inactive', 'draft' => 'yellow',
            'archived' => 'gray',
            default => 'red',
        };
    }

    /**
     * Get {{module_name}} statistics.
     */
    public function toStatisticsArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'status' => $this->model->status,
            'created_at' => $this->model->created_at,
            'updated_at' => $this->model->updated_at,
            'days_since_created' => $this->model->created_at->diffInDays(now()),
            'days_since_updated' => $this->model->updated_at->diffInDays(now()),
        ];
    }
}
