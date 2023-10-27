<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\MigrationDelta
 *
 * @property int $id
 * @property int $to_subscription_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\MigrationDeltaFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta query()
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta whereToSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MigrationDelta whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MigrationDelta extends Model
{
    use HasFactory;

    /**
     * Returns the id of the most recently updated subscription.
     *
     * @return int
     */
    public static function getDeltaId() : int
    {
        $delta = MigrationDelta::select("to_subscription_id")->get();
        return $delta->isEmpty() ? 0 : (int)$delta->first()->to_subscription_id;
    }

    public static function setDeltaId(int $id) : bool
    {
        MigrationDelta::truncate();
        $delta = new MigrationDelta;
        $delta->to_subscription_id = $id;
        return $delta->save();
    }
}
