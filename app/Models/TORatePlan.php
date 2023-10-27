<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TORatePlan
 *
 * @property int $id
 * @property int $newproduct_id
 * @property string|null $name
 * @property string|null $alias
 * @property int|null $term
 * @property string $one_time
 * @property string $recurring
 * @property int $term_default
 * @property int $display
 * @property string $created
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereNewproductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereOneTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORatePlan whereTermDefault($value)
 * @mixin \Eloquent
 */
class TORatePlan extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "new_rate_plans";
}
