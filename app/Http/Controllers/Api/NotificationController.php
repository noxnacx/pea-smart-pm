<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 1. ดึงรายการแจ้งเตือนทั้งหมด
    public function index(Request $request)
    {
        return response()->json($request->user()->notifications);
    }

    // 2. ดึงจำนวนที่ยังไม่อ่าน (ตัวเลขสีแดง)
    public function unreadCount(Request $request)
    {
        return response()->json(['count' => $request->user()->unreadNotifications->count()]);
    }

    // 3. กดอ่านแจ้งเตือน (Mark as Read)
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Read']);
    }

    // 4. อ่านทั้งหมด
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All Read']);
    }
}
