<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\VideoChapterUpsertRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class VideoChapterStoreRequest extends FormRequest
{
    use VideoChapterUpsertRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $this->videoChapterUpsertRules();
    }

    public function messages()
    {
        return $this->videoChapterUpsertMessages();
    }
}
