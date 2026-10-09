<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUlid
{
    /**
     * Boot the trait and auto-generate ULID on model creation.
     */
    protected static function bootHasUlid(): void
    {
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * Scope query to find by ULID instead of ID.
     */
    public function scopeWhereUlid($query, string $ulid)
    {
        return $query->where('ulid', $ulid);
    }

    /**
     * Find a model by ULID.
     */
    public static function findByUlid(string $ulid): ?static
    {
        return static::where('ulid', $ulid)->first();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Retrieve the model for a bound value (supports both ULID and ID fallback).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'ulid', $value)
            ->orWhere('id', $value)
            ->first();
    }

    /**
     * Find a model by ULID or fail.
     */
    public static function findByUlidOrFail(string $ulid): static
    {
        return static::where('ulid', $ulid)->firstOrFail();
    }
}
