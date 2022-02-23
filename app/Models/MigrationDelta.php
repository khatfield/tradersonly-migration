<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
