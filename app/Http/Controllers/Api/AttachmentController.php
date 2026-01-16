<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * อัปโหลดไฟล์แนบและบันทึกลงฐานข้อมูล
     * รองรับทั้ง Task และ Payment ผ่าน Polymorphic Relation
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // จำกัดขนาดไฟล์ไม่เกิน 10MB
            'attachable_id' => 'required|integer',
            'attachable_type' => 'required|string', // เช่น 'App\Models\Task' หรือ 'App\Models\Payment'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // บันทึกไฟล์ลงใน storage/app/public/attachments
            $path = $file->store('attachments', 'public');

            // สร้าง Record ในฐานข้อมูล
            $attachment = Attachment::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'attachable_id' => $request->attachable_id,
                'attachable_type' => $request->attachable_type,
                'user_id' => $request->user()->id, // เก็บ ID ผู้ที่ทำการอัปโหลด
            ]);

            // ✅ บันทึก Audit Log เชื่อมโยงไปยัง Parent Model (Project/Task)
            // เพื่อให้ประวัติไปขึ้นที่หน้าโครงการ
            $parentClass = $request->attachable_type;

            // ตรวจสอบว่า Class นี้มีอยู่จริงไหม
            if (class_exists($parentClass)) {
                $parent = $parentClass::find($request->attachable_id);

                if ($parent) {
                    activity()
                        ->performedOn($parent) // ให้ Log นี้ไปแปะอยู่ที่ Project/Task นั้นๆ
                        ->causedBy($request->user())
                        ->withProperties(['file_name' => $file->getClientOriginalName()])
                        ->log("แนบไฟล์เอกสาร: {$file->getClientOriginalName()}");
                }
            }

            return response()->json([
                'message' => 'อัปโหลดไฟล์สำเร็จ',
                'attachment' => $attachment,
                'url' => asset('storage/' . $path) // ส่ง URL สำหรับเข้าถึงไฟล์กลับไปให้หน้าบ้าน
            ], 201);
        }

        return response()->json(['message' => 'ไม่พบไฟล์ที่ส่งมา'], 400);
    }

    /**
     * ลบไฟล์แนบ (ลบทั้งใน Storage และ Database)
     */
    public function destroy($id)
    {
        $attachment = Attachment::findOrFail($id);

        // ตรวจสอบและลบไฟล์จริงออกจาก Storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // (Optional) อาจจะเพิ่ม Log ตอนลบไฟล์ด้วยก็ได้ ถ้าต้องการ
        /*
        $parent = $attachment->attachable;
        if ($parent) {
             activity()
                ->performedOn($parent)
                ->causedBy(request()->user())
                ->log("ลบไฟล์เอกสาร: {$attachment->file_name}");
        }
        */

        // ลบข้อมูลในฐานข้อมูล
        $attachment->delete();

        return response()->json(['message' => 'ลบไฟล์แนบเรียบร้อยแล้ว']);
    }

    // ดึงรายการไฟล์ (รองรับทั้ง Project, Task, Payment)
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        return Attachment::with('user')
            ->where('attachable_type', $request->type)
            ->where('attachable_id', $request->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
