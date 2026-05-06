<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'file_number' => 'nullable|string|max:100',
            'category_id' => 'required|exists:categories,id',
            'issue_date' => 'required|date',
            'archive_type' => 'required|in:full,physical_only,placeholder',
            'privacy_type' => 'required|in:public,private,specific_user,specific_department',
            'download_policy' => 'required|in:direct_download,request_to_pic',
            'pic_user_id' => 'required|exists:users,id',
            'file' => 'required_if:archive_type,full|file|mimes:pdf,doc,docx,jpg,png|max:20480',
            'expire_date' => 'nullable|date',
            'reminder_date' => 'required_with:expire_date|nullable|date',
            'company_id' => 'required|exists:companies,id',
        ];
    }
}
