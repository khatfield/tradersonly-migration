<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TOInvoice
 *
 * @property int $id
 * @property string $invoice_number
 * @property int $user_id
 * @property int|null $admin_id
 * @property string $amount
 * @property int $renewal
 * @property int $ancillary
 * @property string|null $notes
 * @property string|null $paid
 * @property string $created
 * @property string|null $deleted
 * @property-read \App\Models\TOUser|null $orderCreator
 * @property-read \App\Models\TOPayment|null $payment
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereAncillary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice wherePaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereRenewal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOInvoice whereUserId($value)
 * @mixin \Eloquent
 */
class TOInvoice extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "invoices";

    public function payment()
    {
        return $this->hasOne(TOPayment::class, "invoice_id", "id");
    }

    public function orderCreator()
    {
        return $this->hasOne(TOUser::class, "id", "admin_id");
    }
}
