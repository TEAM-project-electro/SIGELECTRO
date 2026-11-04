<?php

namespace App\Http\Requests\Avance;

use App\Models\AvancePresupuestal;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proyecto = $this->route('proyecto');
        return $this->user()->can('create', [AvancePresupuestal::class, $proyecto]);
    }

    public function rules(): array
    {
        $proyecto = $this->route('proyecto');
        $montoMax = $proyecto ? (float) $proyecto->monto : 0;

        return [
            'fecha_reporte' => ['required', 'date'],
            'presupuesto_planificado' => ['required', 'numeric', 'min:0', "max:{$montoMax}"],
            'gasto_acumulado' => ['required', 'numeric', 'min:0', "max:{$montoMax}"],
        ];
    }

    public function messages(): array
    {
        return [
            'presupuesto_planificado.max' => 'Regla R1: El presupuesto planificado no puede superar el monto total del proyecto (S/ ' . number_format($this->route('proyecto')->monto ?? 0, 2) . ').',
            'gasto_acumulado.max' => 'Regla R1: El gasto acumulado no puede superar el monto total del proyecto (S/ ' . number_format($this->route('proyecto')->monto ?? 0, 2) . ').',
            'fecha_reporte.required' => 'La fecha de corte es obligatoria.',
        ];
    }
}
