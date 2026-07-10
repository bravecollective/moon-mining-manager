<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Whitelist
 *
 * @property int $id
 * @property int $eve_id
 * @property int $is_admin
 * @property int $form_mail
 * @property int $added_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User $whitelister
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereEveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereFormMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Whitelist whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Whitelist extends Model
{

    protected $table = 'whitelist';

    /**
     * Get the user record associated with the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'eve_id', 'eve_id');
    }

    /**
     * Get the user record associated with the user who added this user.
     */
    public function whitelister()
    {
        return $this->belongsTo(User::class, 'added_by', 'eve_id');
    }

}
