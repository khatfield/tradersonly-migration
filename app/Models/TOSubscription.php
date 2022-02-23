<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TOSubscription extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "subscriptions";

    public function user()
    {
        return $this->belongsTo(TOUser::class, "user_id", "id");
    }

    public function invoice()
    {
        return $this->belongsTo(TOInvoice::class, "invoice_id", "id");
    }

    public function renewalRatePlan()
    {
        return $this->belongsTo(TORatePlan::class, "renewal_plan", "id");
    }

    public function autoRenew()
    {
        return $this->belongsTo(TOAutoRenew::class, "new_subscription_id", "id");
    }
}
