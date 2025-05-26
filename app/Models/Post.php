<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Scopes\PublishedScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Post extends Model
{
    protected $guarded = [
        'id',
        'created_at'
    ];

    // Global scope
    protected static function booted()
    {
        static::addGlobalScope(PublishedScope::class);
    }

    function casts()
    {
        return [
            'status' => PostStatus::class,
            'tags' => 'array',
        ];
    }


    function platforms()
    {
        return $this->belongsToMany(Platform::class, 'post_platform')->withPivot('platform_status');
    }
    function user()
    {
        return $this->belongsTo(User::class);
    }


    // SCOPES

    function scopeFilter($query, $request)
    {

        if (sizeof($request) > 0) {

            return $query->when(isset($request['status']), function ($query) use ($request) {
                $query->where('status', $request['status']);
            })
                ->when(isset($request['date']), function ($query) use ($request) {
                    $query->where(function ($date) use ($request) {
                        $date->whereDate('published_at', $request['date'])
                            ->orWhereDate('scheduled_time', $request['date']);
                    });
                });
        }
        return;
    }
}