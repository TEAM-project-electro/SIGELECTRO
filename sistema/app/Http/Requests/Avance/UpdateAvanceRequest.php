<?php

namespace App\Http\Requests\Avance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $avance = $this->route('avance');
        return $this->user()->can('update', $avance);
    }

    public function rules(): array
    {
        $avance = $this->route('avance');
        $proyecto = $avance ? $avance->proyecto : null;
        $montoMax = $proyecto ? (float) $proyecto->monto : 0;

        return [
            'revision_esperada' => ['required', 'integer', 'min:1'],
            'presupuesto_planificado' => ['required', 'numeric', 'min:0', "max:{$montoMax}"],
            'gasto_acumulado' => ['required', 'numeric', 'min:0', "max:{$montoMax}"],
        ];
    }

    public function messages(): array
    {
        return [
            'presupuesto_planificado.max' => 'Regla R1: El presupuesto planificado no puede superar el monto total del proyecto.',
            'gasto_acumulado.max' => 'Regla R1: El gasto acumulado no puede superar el monto total del proyecto.',
        ];
    }
}
