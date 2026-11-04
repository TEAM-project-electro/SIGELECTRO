<?php

namespace App\Http\Requests\Proyecto;

use Illuminate\Foundation\Http\FormRequest;

class ReabrirProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proyecto = $this->route('proyecto');
        return $this->user()->can('reabrir', $proyecto);
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de reapertura es obligatorio para registrar la bitácora técnica.',
            'motivo.min' => 'El motivo debe contener al menos 5 caracteres justificativos.',
        ];
    }
}
