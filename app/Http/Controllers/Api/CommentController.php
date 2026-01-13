<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;

class CommentController extends Controller
{
    // ดึงคอมเมนต์ของ Project หรือ Task
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|in:project,task',
            'id' => 'required|integer'
        ]);

        // Map ชื่อ type ให้ตรงกับ Class จริง
        $modelClass = $request->type === 'project' ? Project::class : Task::class;

        $comments = Comment::where('commentable_type', $modelClass)
            ->where('commentable_id', $request->id)
            ->with('user') // ดึงข้อมูลคนพิมพ์มาด้วย
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    // บันทึกคอมเมนต์ใหม่
    public function store(Request $request)
    {
        $request->validate([
            'body' => 'required|string',
            'type' => 'required|in:project,task',
            'id' => 'required|integer'
        ]);

        $modelClass = $request->type === 'project' ? Project::class : Task::class;

        $comment = Comment::create([
            'body' => $request->body,
            'user_id' => $request->user()->id,
            'commentable_type' => $modelClass,
            'commentable_id' => $request->id
        ]);

        // Return พร้อมข้อมูล User เพื่อเอาไปแสดงผลทันทีไม่ต้อง Refresh
        return response()->json($comment->load('user'));
    }
}
