<?php

namespace App\Http\Requests\Proyecto;

use Illuminate\Foundation\Http\FormRequest;

class CerrarProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proyecto = $this->route('proyecto');
        return $this->user()->can('cerrar', $proyecto);
    }

    public function rules(): array
    {
        return [
            'confirmacion' => ['nullable', 'string'],
        ];
    }
}
