<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TOPayment
 *
 * @property int $id
 * @property int $user_id
 * @property int $invoice_id
 * @property string $method
 * @property string $amount
 * @property int|null $paymentprofile_id
 * @property string|null $transaction_id
 * @property string|null $processor
 * @property string|null $auth_code
 * @property string|null $auth_data
 * @property string $created
 * @property string|null $deleted
 * @property-read \App\Models\TOPaymentProfile|null $profile
 * @property-read \App\Models\TORefund|null $refund
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereAuthCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereAuthData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment wherePaymentprofileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereProcessor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOPayment whereUserId($value)
 * @mixin \Eloquent
 */
class TOPayment extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "payments";

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|TOPaymentProfile
     */
    public function profile()
    {
        return $this->belongsTo(TOPaymentProfile::class, "paymentprofile_id", "id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|TORefund
     */
    public function refund()
    {
        return $this->belongsTo(TORefund::class, "id", "payment_id");
    }
}
