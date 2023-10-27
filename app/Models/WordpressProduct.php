<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\WordpressProduct
 *
 * @property int $id
 * @property int $product_id
 * @property int $attribute_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordpressProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WordpressProduct extends Model
{
    use HasFactory;
}
