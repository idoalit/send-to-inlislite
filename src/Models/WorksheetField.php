<?php

namespace Idoalit\S2i\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorksheetField extends Model
{
    protected $connection= 'inlislite';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'worksheetfields';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['ID'];

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    const CREATED_AT = 'CreateDate';
    const UPDATED_AT = 'UpdateDate';

    function field() : HasOne {
        return $this->hasOne(Field::class, 'ID', 'Field_id');
    }
}