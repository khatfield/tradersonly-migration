<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LegacyMap
 *
 * @property int $id
 * @property int|null $wp_user
 * @property int|null $legacy_user
 * @property int|null $wp_sub
 * @property int|null $legacy_sub
 * @property int|null $wp_order
 * @property string|null $wp_order_status
 * @property int|null $legacy_order
 * @property \Illuminate\Support\Carbon|null $sub_start
 * @property \Illuminate\Support\Carbon|null $order_date
 * @property string|null $order_total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap query()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereLegacyOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereLegacySub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereLegacyUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereOrderTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereSubStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereWpOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereWpOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereWpSub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyMap whereWpUser($value)
 * @mixin \Eloquent
 */
class LegacyMap extends Model
{
    use HasFactory;

    protected $connection = 'wpto';
    protected $guarded = [];

    protected $dates = ['order_date', 'sub_start'];
}
