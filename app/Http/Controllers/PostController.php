<?php

namespace App\Http\Controllers;

use App\Actions\AttachPostToPlatforms;
use App\Actions\DetachPostToPlatforms;
use App\Enums\PostStatus;
use App\Enums\PublishingOptions;
use App\Http\Requests\PostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PlatformResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PlatformService;
use App\Services\PostService;
use App\Traits\hasAttachments;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PostController extends Controller implements HasMiddleware
{
    use hasAttachments, AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(
                'throttle:limit-post',
                only: ['store']
            ),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PlatformService $platform_service, PostService $post_service)
    {
        $posts = Post::with('platforms', 'user')
            ->Filter([...$request->query()])
            ->withoutGlobalScopes()
            ->paginate(7)
            ->withQueryString();

        return Inertia::render('Posts/Index', [
            'posts' => PostResource::collection($posts),
            'publishStatus' => PostStatus::toArray(),
            'postCountPerPlatform' => $platform_service->getPostCountForEachStatus(),
            'filters' => [...$request->query()]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, PlatformService $platform_service)
    {
        // Handling both requests to decide what is the best response.

        // axios response
        if (!$request->hasHeader('X-Inertia')) {
            return response()->json([
                'platforms' => $platform_service->getEnabledPlatforms(),
                'publishedOptions' => PublishingOptions::toArray(),
            ]);
        }

        // interia response
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request, PostService $post_services, AttachPostToPlatforms $action)
    {

        $scheduled_time = $request->scheduledDate ? Carbon::parse($request->scheduledDate) : null;

        if ($request->publishOption != PostStatus::DRAFT->value) {
            $request->publishOption = $post_services->isScheduledTimeGreaterThanNow($scheduled_time)
                ? ucfirst($request->publishOption)
                : PostStatus::PUBLISHED->value;
        }

        // Move image to new path.
        $request->image = $this->moveToNewPath($request->image);


        try {
            // Creating Post
            $post = Post::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'content' => $request->content,
                'image_url' => $request->image,
                'scheduled_time' => $scheduled_time,
                'published_at' => !$post_services->isScheduledTimeGreaterThanNow($scheduled_time)
                ? now()
                : null,
                'status' => $request->publishOption,
                'tags' => $request->tags,
            ]);

            // Attaching Post to mulitple platform for cross posting at the same time.
            $action->handle($post, $request->platforms, $request->publishOption);


            // The data will be reflected in the database only if both the post and its associated platforms are successfully created.
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($post, Request $request, PlatformService $platform_service)
    {
        $post = Post::withoutGlobalScopes()
            ->with('platforms')
            ->findOrFail($post);

        $this->authorize('view', $post);
        // Handling both requests to decide what is the best response.
        // axios response
        if (!$request->hasHeader('X-Inertia')) {
            return response()->json([
                'post' => new PostResource($post),
                'attatchedPlatforms' => PlatformResource::collection($platform_service->getAttachedPlatforms($post) ?? []),
                'unAttatchedPlatforms' => PlatformResource::collection($platform_service->getUnAttachedPlatforms($post) ?? [])
            ]);
        }

        // interia response
        return Inertia::render('Posts/Index', [
            'post' => new PostResource($post),
            'attatchedPlatforms' => PlatformResource::collection($platform_service->getAttachedPlatforms($post) ?? []),
            'unAttatchedPlatforms' => PlatformResource::collection($platform_service->getUnAttachedPlatforms($post) ?? [])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($post, UpdatePostRequest $request, PostService $post_services, AttachPostToPlatforms $attach_post_to_platform, DetachPostToPlatforms $detach_post_from_platform)
    {
        $post = Post::withoutGlobalScopes()->findOrFail($post);
        $this->authorize('update', $post);
        $scheduled_time = $request->scheduledDate ? Carbon::parse($request->scheduledDate) : null;

        if ($request->publishOption != PostStatus::DRAFT->value) {
            $request->publishOption = $post_services->isScheduledTimeGreaterThanNow($scheduled_time)
                ? ucfirst($request->publishOption)
                : PostStatus::PUBLISHED->value;
        }

        // Move image to new path.
        $request->image = $this->moveToNewPath($request->image);

        try {
            // Creating Post
            $post->update([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'content' => $request->content,
                'image_url' => $request->image,
                'scheduled_time' => $scheduled_time,
                'published_at' => !$post_services->isScheduledTimeGreaterThanNow($scheduled_time)
                ? now()
                : null,
                'status' => $request->publishOption,
                'tags' => $request->tags,
            ]);

            // Attaching Post to mulitple platform for cross posting at the same time.
            $attach_post_to_platform->handle($post, $request->platforms, $request->publishOption);

            // Detach Post from platforms.
            $detach_post_from_platform->handle($post, $request->removedPlatforms);



            // The data will be reflected in the database only if both the post and its associated platforms are successfully created.
            DB::commit();
        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($post, Request $request)
    {
        $post = Post::withoutGlobalScopes()->findOrFail($post);
        $this->authorize('delete', $post);
        $post->delete();
    }
}