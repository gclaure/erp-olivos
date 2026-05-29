<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Company|null getCompany()
 * @method static string|null getTenantId()
 * @method static \App\Models\Company update(array $data)
 *
 * @see \App\Services\CompanyService
 */
class CompanyFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'company.service';
    }
}
