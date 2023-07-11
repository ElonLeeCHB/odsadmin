<?php
/**
 * 本檔應廢棄，改用 App\Models\Common 裡面的
 */
namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Model;
use App\Models\Counterparty\Organization;

class OrganizationMeta extends Model
{
    protected $guarded = [];
    protected $table = 'organization_meta';
    public $timestamps = false;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
