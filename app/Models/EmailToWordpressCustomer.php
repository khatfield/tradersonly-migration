<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EmailToWordpressCustomer
 *
 * @property int $id
 * @property string $email
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailToWordpressCustomer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailToWordpressCustomer extends Model
{
    use HasFactory;
}
