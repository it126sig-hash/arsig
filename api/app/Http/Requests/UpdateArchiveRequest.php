<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'keterangan'      => 'nullable|string|max:1000',
            'file_number'     => 'nullable|string|max:100',
            'category_id'     => 'required|exists:categories,id',
            'issue_date'      => 'required|date',
            'archive_type'    => 'required|in:full,physical_only,digital_only,placeholder',
            'privacy_type'    => 'required|in:public,private,department,user',
            'download_policy' => 'required|in:direct_download,request_to_pic',
            'is_confidential' => 'sometimes|boolean',
            'pic_user_id'     => 'required|exists:users,id',
            'file'            => 'nullable|file|max:20480',
            'expire_date'     => 'nullable|date',
            'reminder_date'   => 'required_with:expire_date|nullable|date',
            'company_id'      => 'required|exists:companies,id',
            'department_ids'  => 'required_if:privacy_type,department|array',
            'department_ids.*' => 'exists:departments,id',
            'user_ids'        => 'required_if:privacy_type,user|array',
            'user_ids.*'      => 'exists:users,id',
            'tag_ids'         => 'nullable|array',
            'tag_ids.*'       => 'exists:tags,id',
            // Catatan: floor_id, room_id, cabinet_id, cabinet_slot_id TIDAK diizinkan
            // di endpoint update archive. Gunakan endpoint "Pindah Lokasi" khusus.
        ];
    }
}
