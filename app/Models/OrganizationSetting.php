<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganizationSetting extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $table = 'organization_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id', 'group', 'key', 'value', 'value_type',
    ];
}
