<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait CoopScoped
{
    protected static function bootCoopScoped(): void
    {
        static::addGlobalScope('coop', function (Builder $builder) {
            $user = auth()->user();

            if (!$user || !$user->coop_id) {
                return;
            }

            if (static::canBypassCoopScope($user)) {
                return;
            }

            $model = $builder->getModel();
            $key = method_exists($model, 'getCoopScopeKey') ? $model->getCoopScopeKey() : 'coop_id';
            $builder->where($model->getTable() . '.' . $key, $user->coop_id);
        });

        static::creating(function (Model $model) {
            static::attachCoopId($model);
        });

        static::saving(function (Model $model) {
            static::attachCoopId($model);
        });
    }

    protected function getCoopScopeKey(): string
    {
        return property_exists($this, 'coopScopeKey') ? $this->coopScopeKey : 'coop_id';
    }

    protected function shouldAttachCoopId(): bool
    {
        return !property_exists($this, 'attachCoopId') || $this->attachCoopId;
    }

    protected static function attachCoopId(Model $model): void
    {
        $user = auth()->user();

        if (!$user || !$user->coop_id) {
            return;
        }

        if (static::canBypassCoopScope($user)) {
            return;
        }

        if (method_exists($model, 'shouldAttachCoopId') && !$model->shouldAttachCoopId()) {
            return;
        }

        $key = method_exists($model, 'getCoopScopeKey') ? $model->getCoopScopeKey() : 'coop_id';
        $model->setAttribute($key, $user->coop_id);
    }

    protected static function canBypassCoopScope($user): bool
    {
        return $user->can('view-all-cooperatives');
    }
}
