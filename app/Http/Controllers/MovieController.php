<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show']]);
    }

    public function index(Request $request)
    {
        $perpage  = $request->query('perpage', 10);
        $page     = $request->query('page', 1);
        $keywords = $request->query('keywords');

        $skip = ($page - 1) * $perpage;

        $query = Movie::query();

        if ($keywords) {
            $query->where('title', 'LIKE', '%' . $keywords . '%');
            $query->orWhere('description', 'LIKE', '%' . $keywords . '%');
            $query->orWhere('artists', 'LIKE', '%' . $keywords . '%');
            $query->orWhere('genres', 'LIKE', '%' . $keywords . '%');
        }

        $movies = $query->skip($skip)->take($perpage)->get();

        return response()->json($movies);
    }

    public function show(string $id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json(['message' => 'Movie not found'], 404);
        }

        $movie->total_watched = ($movie->total_watched + 1);
        $movie->save();

        return response()->json($movie, 200);
    }

    public function mostViewed()
    {
        $movie = Movie::orderBy('total_watched', 'desc')->first();

        if (!$movie) {
            return response()->json(['message' => 'Movie not found'], 404);
        }

        return response()->json($movie, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(request()->all(),[
            'title'         => 'required',
            'description'   => 'required',
            'duration'      => 'required',
            'artists'       => 'required',
            'genres'        => 'required',
            'video'         => 'required|file|mimes:mp4,avi,wmv',
        ]);

        if($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $movie = new Movie;
        $movie->title       = $request->input('title');
        $movie->description = $request->input('description');
        $movie->duration    = $request->input('duration');
        $movie->artists     = $request->input('artists');
        $movie->genres      = $request->input('genres');

        $videoPath          = $request->file('video')->store('videos', 'public');
        $movie->url         = $videoPath;

        $movie->save();

        return response()->json($movie, 201);
    }

    public function update(Request $request, string $id)
    {
        $validator = Validator::make(request()->all(),[
            'title'         => 'required',
            'description'   => 'required',
            'duration'      => 'required',
            'artists'       => 'required',
            'genres'        => 'required',
            'video'         => 'file|mimes:mp4,avi,wmv',
        ]);

        if($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json(['message' => 'Movie not found'], 404);
        }

        $movie->title       = $request->input('title');
        $movie->description = $request->input('description');
        $movie->duration    = $request->input('duration');
        $movie->artists     = $request->input('artists');
        $movie->genres      = $request->input('genres');

        if ($request->hasFile('video')) {
            $videoPath  = $request->file('video')->store('videos', 'public');
            Storage::disk('public')->delete($movie->url);

            $movie->url = $videoPath;
        }

        $movie->save();

        return response()->json($movie, 200);
    }

    public function destroy(string $id)
    {
        //
    }
}
