<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\LiveChapterUpsertRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class LiveChapterStoreRequest extends FormRequest
{
    use LiveChapterUpsertRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $this->liveChapterUpsertRules();
    }

    public function messages()
    {
        return $this->liveChapterUpsertMessages();
    }
}
