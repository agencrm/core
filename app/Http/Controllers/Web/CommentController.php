<?php

// app/Http/Controllers/Web/CommentController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Show the list of comments.
     */
    public function index()
    {
        //$comments = Comment::latest()->paginate(15);

        return Inertia::render('Comments/CommentIndex', [
            //'comments' => $comments,
        ]);
    }

    /**
     * Show a single comment by id.
     */
    public function show(int $id)
    {
        $comment = Comment::findOrFail($id);

        return Inertia::render('Comments/ShowComment', [
            'comment' => $comment,
        ]);
    }
}
