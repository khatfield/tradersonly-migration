<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TOUser
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $sf_id
 * @property int $sf_buy_back_program
 * @property string|null $z_acct_id
 * @property string|null $auth_id
 * @property string|null $auth_loc
 * @property string|null $gp_id
 * @property string|null $auth_code
 * @property string|null $billing_state
 * @property string|null $billing_country
 * @property string|null $product
 * @property string|null $version
 * @property int $mobile
 * @property int $admin
 * @property int $comped
 * @property int $returned
 * @property int $archive
 * @property int $vp_sendback
 * @property string|null $sub_initial
 * @property string|null $sub_expire
 * @property int|null $mobile_term
 * @property int|null $mobile_max_devices
 * @property int|null $mobile_max_deletes
 * @property string|null $last_download
 * @property string $ip
 * @property string|null $last_login
 * @property int $temp_pass
 * @property string $created
 * @property string|null $deleted
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereArchive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereAuthCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereAuthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereAuthLoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereBillingCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereBillingState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereComped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereGpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereLastDownload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereMobileMaxDeletes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereMobileMaxDevices($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereMobileTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereProduct($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereSfBuyBackProgram($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereSfId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereSubExpire($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereSubInitial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereTempPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereVpSendback($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TOUser whereZAcctId($value)
 * @mixin \Eloquent
 */
class TOUser extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "users";
}
