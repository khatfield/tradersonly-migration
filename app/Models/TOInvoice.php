<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
