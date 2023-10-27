<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TOPaymentProfile
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $auth_loc
 * @property int|null $auth_id
 * @property int|null $pay_profile_id
 * @property string|null $card_type
 * @property string|null $expiration
 * @property string|null $errors
 * @property string $created
 * @property string|null $deleted
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereAuthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereAuthLoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereCardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile wherePayProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPaymentProfile whereUserId($value)
 * @mixin \Eloquent
 */
class TOPaymentProfile extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "payment_profiles";
}
