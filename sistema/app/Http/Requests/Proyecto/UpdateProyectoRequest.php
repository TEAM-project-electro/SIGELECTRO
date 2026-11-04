<?php

namespace App\Http\Requests\Proyecto;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proyecto = $this->route('proyecto');
        return $this->user()->can('update', $proyecto);
    }

    public function rules(): array
    {
        $proyecto = $this->route('proyecto');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'min:0'],
            'anio_inicio' => ['required', 'integer', 'min:2020', 'max:2035'],
            'anio_fin' => ['required', 'integer', 'gte:anio_inicio', 'max:2035'],
            'id_objetivo' => ['required', 'integer', 'exists:objetivo_gd,id_objetivo'],
            'id_responsable' => ['required', 'integer', 'exists:responsable,id_responsable'],
            'fuente' => ['nullable', 'string', 'max:100'],
            'fecha_actualizacion_esperada' => ['required'],
            'motivo_cambio' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'anio_fin.gte' => 'El año de fin no puede ser anterior al año de inicio.',
            'monto.min' => 'El monto del proyecto no puede ser negativo.',
            'fecha_actualizacion_esperada.required' => 'Es obligatorio enviar el control de versión/concurrencia.',
        ];
    }
}
