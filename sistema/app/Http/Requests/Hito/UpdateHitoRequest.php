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
                $cambioLb = (int) $hito->en_linea_base !== $nuevaLb;
                $fechaOriginal = $hito->fecha_prevista instanceof \DateTimeInterface
                    ? $hito->fecha_prevista->format('Y-m-d')
                    : (string) $hito->fecha_prevista;
                $fechaNueva = substr(trim((string) $this->input('fecha_prevista')), 0, 10);
                $cambioFechaLb = (int) $hito->en_linea_base === 1
                    && $this->filled('fecha_prevista')
                    && $fechaNueva !== $fechaOriginal;

                if ($cambioFechaLb && !$this->user()->esAdmin()) {
                    $validator->errors()->add('fecha_prevista', 'Solo un Administrador puede reprogramar la fecha prevista de un hito en línea base.');
                    return;
                }

                if ($cambioLb || $cambioFechaLb) {
                    $motivo = trim((string) $this->input('motivo_cambio', ''));
                    if (mb_strlen($motivo) < 5) {
                        $validator->errors()->add('motivo_cambio', 'Modificar la línea base o reprogramar su fecha prevista exige una justificación de al menos 5 caracteres.');
                    }
                }
            }
        });
    }
}
