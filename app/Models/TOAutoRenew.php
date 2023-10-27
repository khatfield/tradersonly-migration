<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TOAutoRenew
 *
 * @property int $id
 * @property int $user_id
 * @property int $invoice_id
 * @property int|null $original_subscription_id
 * @property int|null $original_zsubscription_id
 * @property int|null $original_term
 * @property string|null $original_cost
 * @property int|null $new_subscription_id
 * @property int|null $new_term
 * @property string|null $new_cost
 * @property int $success
 * @property string|null $result
 * @property string $created
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew query()
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereNewCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereNewSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereNewTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereOriginalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereOriginalSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereOriginalTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereOriginalZsubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereSuccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOAutoRenew whereUserId($value)
 * @mixin \Eloquent
 */
class TOAutoRenew extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "auto_renews";
}
