<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function show(Request $request)
    {
        $setting = Setting::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['data' => $this->defaults()]
        );

        $data = $setting->data ?? $this->defaults();
        if (!empty($data['logo_path']) && !str_starts_with($data['logo_url'] ?? '', 'http')) {
            $data['logo_url'] = Storage::disk('r2')->url($data['logo_path']);
        }

        return response()->json($data);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nome_empresa'             => 'nullable|string|max:255',
            'moeda'                    => 'nullable|in:BRL,USD,EUR',
            'fuso_horario'             => 'nullable|string|max:100',
            'auto_atribuir_leads'      => 'boolean',
            'email_notificacao_leads'  => 'nullable|email|max:255',
            'dias_expirar_lead'        => 'nullable|integer|min:1|max:365',
            'orcamento_padrao'         => 'nullable|numeric|min:0',
            'dominio_tracking'         => 'nullable|string|max:255',
            'notificar_novo_lead'      => 'boolean',
            'notificar_tarefa_vencida' => 'boolean',
            'notificar_fim_campanha'   => 'boolean',
            'cor_primaria'             => 'nullable|string|max:7',
        ]);

        $setting = Setting::firstOrCreate(['user_id' => $request->user()->id]);
        $merged  = array_merge($this->defaults(), $setting->data ?? [], $validated);
        $setting->update(['data' => $merged]);

        return response()->json($merged);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        $setting = Setting::firstOrCreate(['user_id' => $request->user()->id]);

        // Delete old logo if exists
        if (!empty($setting->data['logo_path'])) {
            Storage::disk('r2')->delete($setting->data['logo_path']);
        }

        $path = $request->file('logo')->store(
            'logos/' . $request->user()->id,
            'public'
        );

        $url  = Storage::disk('r2')->url($path);
        $data = array_merge($this->defaults(), $setting->data ?? [], [
            'logo_path' => $path,
            'logo_url'  => $url,
        ]);
        $setting->update(['data' => $data]);

        return response()->json(['logo_url' => $url]);
    }

    private function defaults(): array
    {
        return [
            'nome_empresa'             => '',
            'moeda'                    => 'BRL',
            'fuso_horario'             => 'America/Sao_Paulo',
            'auto_atribuir_leads'      => false,
            'email_notificacao_leads'  => '',
            'dias_expirar_lead'        => 30,
            'orcamento_padrao'         => 0,
            'dominio_tracking'         => '',
            'notificar_novo_lead'      => true,
            'notificar_tarefa_vencida' => true,
            'notificar_fim_campanha'   => true,
            'cor_primaria'             => '#7c3aed',
            'logo_path'                => null,
            'logo_url'                 => null,
        ];
    }
}
