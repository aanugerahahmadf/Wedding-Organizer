<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Article::where('is_published', true)->latest()->get(),
        ]);
    }
}
