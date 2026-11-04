<?php

namespace App\Http\Requests\Hito;

use App\Models\Hito;
use Illuminate\Foundation\Http\FormRequest;

class StoreHitoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proyecto = $this->route('proyecto');
        return $this->user()->can('create', [Hito::class, $proyecto]);
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

    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción del hito es obligatoria.',
            'fecha_prevista.required' => 'La fecha prevista es obligatoria.',
        ];
    }
}
