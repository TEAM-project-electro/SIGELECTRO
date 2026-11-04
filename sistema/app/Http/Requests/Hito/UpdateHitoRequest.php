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
            'revision_esperada' => ['required', 'integer', 'min:1'],
            'descripcion' => ['required', 'string', 'max:150'],
            'fecha_prevista' => ['required', 'date'],
            'fecha_cierre' => ['nullable', 'date'],
            'evidencia' => ['nullable', 'string', 'max:200'],
            'en_linea_base' => ['required', 'boolean'],
            'motivo_cambio' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hito = $this->route('hito');
            if ($hito) {
                $nuevaLb = $this->boolean('en_linea_base') ? 1 : 0;
                if ((int) $hito->en_linea_base !== $nuevaLb) {
                    $motivo = trim((string) $this->input('motivo_cambio', ''));
                    if (mb_strlen($motivo) < 5) {
                        $validator->errors()->add('motivo_cambio', 'Al modificar la pertenencia a la línea base de un hito es obligatorio registrar una justificación de al menos 5 caracteres.');
                    }
                }
            }
        });
    }
}
