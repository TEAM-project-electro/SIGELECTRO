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
        return [
            'revision_esperada' => ['required', 'integer', 'min:1'],
            'nombre' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'min:0'],
            'anio_inicio' => ['required', 'integer', 'min:2020', 'max:2035'],
            'anio_fin' => ['required', 'integer', 'gte:anio_inicio', 'max:2035'],
            'id_objetivo' => ['required', 'integer', 'exists:objetivo_gd,id_objetivo'],
            'id_responsable' => ['required', 'integer', 'exists:responsable,id_responsable'],
            'fuente' => ['nullable', 'string', 'max:100'],
            'fecha_actualizacion_esperada' => ['required'],
            'motivo_cambio' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $proyecto = $this->route('proyecto');

            if ($proyecto) {
                // A01: Un usuario con perfil 'Responsable' no puede modificar campos reservados al Administrador
                if ($user && $user->esResponsable()) {
                    if ($this->has('monto') && (float) $this->monto !== (float) $proyecto->monto) {
                        $validator->errors()->add('monto', 'Solo el Administrador tiene autorización para modificar el presupuesto del proyecto.');
                    }
                    if ($this->has('id_responsable') && (int) $this->id_responsable !== (int) $proyecto->id_responsable) {
                        $validator->errors()->add('id_responsable', 'Solo el Administrador tiene autorización para reasignar el responsable del proyecto.');
                    }
                    if ($this->has('id_objetivo') && (int) $this->id_objetivo !== (int) $proyecto->id_objetivo) {
                        $validator->errors()->add('id_objetivo', 'Solo el Administrador tiene autorización para modificar el objetivo estratégico del proyecto.');
                    }
                    if ($this->has('anio_inicio') && (int) $this->anio_inicio !== (int) $proyecto->anio_inicio) {
                        $validator->errors()->add('anio_inicio', 'Solo el Administrador tiene autorización para modificar los años de vigencia del proyecto.');
                    }
                    if ($this->has('anio_fin') && (int) $this->anio_fin !== (int) $proyecto->anio_fin) {
                        $validator->errors()->add('anio_fin', 'Solo el Administrador tiene autorización para modificar los años de vigencia del proyecto.');
                    }
                }

                // A15: Reducción del presupuesto requiere obligatoriamente justificación registrada (5-200 caracteres)
                if ($this->has('monto') && (float) $this->monto < (float) $proyecto->monto) {
                    $motivo = trim((string) $this->input('motivo_cambio', ''));
                    if (mb_strlen($motivo) < 5) {
                        $validator->errors()->add('motivo_cambio', 'Al reducir el presupuesto del proyecto es obligatorio registrar una justificación detallada de al menos 5 caracteres.');
                    }
                }
            }
        });
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
