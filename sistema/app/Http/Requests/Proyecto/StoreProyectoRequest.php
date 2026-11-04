<?php

namespace App\Http\Requests\Proyecto;

use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;

class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Proyecto::class);
    }

    public function rules(): array
    {
        return [
            'codigo_pgd' => ['required', 'string', 'max:10', 'unique:proyecto,codigo_pgd'],
            'nombre' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'min:0'],
            'anio_inicio' => ['required', 'integer', 'min:2020', 'max:2035'],
            'anio_fin' => ['required', 'integer', 'gte:anio_inicio', 'max:2035'],
            'estado' => ['required', 'string', 'in:Pendiente,En curso,Cerrado'],
            'id_objetivo' => ['required', 'integer', 'exists:objetivo_gd,id_objetivo'],
            'id_responsable' => ['required', 'integer', 'exists:responsable,id_responsable'],
            'fuente' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_pgd.unique' => 'El código PGD ya se encuentra registrado en la cartera.',
            'anio_fin.gte' => 'El año de fin no puede ser anterior al año de inicio.',
            'monto.min' => 'El monto del proyecto no puede ser negativo.',
        ];
    }
}
