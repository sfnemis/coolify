<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LocalPersistentVolume extends Model
{
    protected $guarded = [];

    public function application()
    {
        return $this->morphTo();
    }

    public function standalone_postgresql()
    {
        return $this->morphTo();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::of($value)->trim()->value,
        );
    }

    protected function mountPath(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::of($value)->trim()->start('/')->value
        );
    }

    protected function hostPath(): Attribute
    {
        return Attribute::make(
            set: function (string|null $value) {
                if ($value) {
                    return Str::of($value)->trim()->start('/')->value;
                } else {
                    return $value;
                }
            }
        );
    }
}
