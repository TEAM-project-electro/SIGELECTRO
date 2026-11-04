<?php

namespace App\Http\Requests\Hito;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHitoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hito = $this->route('hito');
        return $this->user()->can('update', $hito);
    }

    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:150'],
            'fecha_prevista' => ['required', 'date'],
            'fecha_cierre' => ['nullable', 'date'],
            'evidencia' => ['nullable', 'string', 'max:200'],
            'en_linea_base' => ['required', 'boolean'],
        ];
    }
}
