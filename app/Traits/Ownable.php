<?php namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Class Ownable
 * If not explicitly set, then the entity's owner is set using the authenticated user
 *
 * @package App\Traits
 */
trait Ownable
{
    public static function bootOwnable()
    {
        static::created(function (Model $model) {

            // does model have owner method
            if (method_exists($model, 'owner')) {

                // get authenticated user
                $user = Auth::user();

                // attack owner depending on entity's relationship to owner
                switch (get_class($model->owner())) {
                    case BelongsTo::class:

                        // don't attach if user_id provided
                        if (!is_null($model->user_id)) {
                            return;
                        }

                        $model->owner()->associate($user);
                        break;
                }

                if (!$model->save()) {
                    throw new \Exception("An error occurred while saving owner");
                }
            }
        });
    }
}