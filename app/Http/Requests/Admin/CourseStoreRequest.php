<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\CourseUpsertRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CourseStoreRequest extends FormRequest
{
    use CourseUpsertRequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $this->courseUpsertRules();
    }

    public function messages()
    {
        return $this->courseUpsertMessages();
    }
}
