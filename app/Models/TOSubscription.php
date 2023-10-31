<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TOSubscription
 *
 * @property int                               $id
 * @property int|null                          $version
 * @property int                               $user_id
 * @property int                               $invoice_id
 * @property int                               $initial_term
 * @property int                               $renewal_term
 * @property int|null                          $renewal_plan
 * @property string|null                       $start_date
 * @property string|null                       $expire_date
 * @property bool                              $auto_renew
 * @property bool                              $mobile
 * @property string|null                       $mobile_details
 * @property string                            $created
 * @property string|null                       $canceled
 * @property string|null                       $deleted
 * @property-read \App\Models\TOAutoRenew|null $autoRenew
 * @property-read \App\Models\TOInvoice|null   $invoice
 * @property-read \App\Models\TORatePlan|null  $renewalRatePlan
 * @property-read \App\Models\TOUser|null      $user
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereAutoRenew($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereCanceled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereExpireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereInitialTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereMobileDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereRenewalPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereRenewalTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOSubscription whereVersion($value)
 * @mixin \Eloquent
 */
class TOSubscription extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table      = "subscriptions";

    protected $casts = [
        'auto_renew' => 'boolean',
        'mobile'     => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|TOUser
     */
    public function user()
    {
        return $this->belongsTo(TOUser::class, "user_id", "id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|TOInvoice
     */
    public function invoice()
    {
        return $this->belongsTo(TOInvoice::class, "invoice_id", "id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|TORatePlan
     */
    public function renewalRatePlan()
    {
        return $this->belongsTo(TORatePlan::class, "renewal_plan", "id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|TOAutoRenew
     */
    public function autoRenew()
    {
        return $this->belongsTo(TOAutoRenew::class, "new_subscription_id", "id");
    }
}
