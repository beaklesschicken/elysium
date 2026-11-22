<?php

namespace App\Enums;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

/**
 * Who holds a credential. Values double as the morph aliases in credentials.holder_type.
 */
enum CredentialHolderType: string
{
    case Employee = 'employee';
    case Unit = 'unit';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::Unit => 'Unit',
            self::Organization => 'Organization',
        };
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Employee => Employee::class,
            self::Unit => Unit::class,
            self::Organization => Organization::class,
        };
    }

    public static function fromModel(Model $model): self
    {
        return self::from($model->getMorphClass());
    }
}
