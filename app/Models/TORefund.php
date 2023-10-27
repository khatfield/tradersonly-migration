<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TORefund
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $payment_id
 * @property int|null $zpayment_id
 * @property string|null $method
 * @property string $amount
 * @property string|null $auth_data
 * @property string|null $notes
 * @property string $created
 * @property string|null $deleted
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund query()
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereAuthData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TORefund whereZpaymentId($value)
 * @mixin \Eloquent
 */
class TORefund extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "refunds";
}
