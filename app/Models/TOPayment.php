<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TOPayment extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "payments";

    public function profile()
    {
        return $this->belongsTo(TOPaymentProfile::class, "paymentprofile_id", "id");
    }

    public function refund()
    {
        return $this->belongsTo(TORefund::class, "id", "payment_id");
    }
}
