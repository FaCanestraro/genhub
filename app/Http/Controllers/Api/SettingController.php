<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':settings,view', only: ['show']),
            new Middleware(CheckPermission::class.':settings,edit', only: ['update', 'uploadLogo']),
        ];
    }

    public function show(Request $request)
    {
        $company = $request->company();
        $setting = Setting::firstOrCreate(
            ['company_id' => $company->id],
            ['data' => $this->defaults()]
        );

        $data = array_merge($this->defaults(), $setting->data ?? [], [
            'nome_empresa' => $company->name ?? '',
            'cnpj' => $company->cnpj ?? '',
        ]);

        if (!empty($data['logo_path'])) {
            $data['logo_url'] = Storage::disk('r2')->url($data['logo_path']);
        }

        return response()->json($data);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nome_empresa'             => 'nullable|string|max:255',
            'cnpj'                     => 'nullable|string|max:18',
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

        $company = $request->company();
        $company->fill([
            'name' => $validated['nome_empresa'] ?? $company->name,
            'cnpj' => $validated['cnpj'] ?? $company->cnpj,
        ])->save();

        $settingsData = collect($validated)->except(['nome_empresa', 'cnpj'])->toArray();

        $setting = Setting::firstOrCreate(['company_id' => $company->id]);
        $merged  = array_merge($this->defaults(), $setting->data ?? [], $settingsData);
        $setting->update(['data' => collect($merged)->except(['nome_empresa', 'cnpj'])->toArray()]);

        $response = array_merge($merged, [
            'nome_empresa' => $company->name ?? '',
            'cnpj' => $company->cnpj ?? '',
        ]);

        AuditLogger::log('settings', 'settings.updated', 'Configurações gerais atualizadas', [
            'subject' => $setting,
            'input' => $validated,
        ]);

        return response()->json($response);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        $company = $request->company();
        $setting = Setting::firstOrCreate(['company_id' => $company->id]);

        // Delete old logo if exists
        if (!empty($setting->data['logo_path'])) {
            Storage::disk('r2')->delete($setting->data['logo_path']);
        }

        $path = $request->file('logo')->store(
            'logos/' . $company->id,
            'r2'
        );

        $url  = Storage::disk('r2')->url($path);
        $data = array_merge($this->defaults(), $setting->data ?? [], [
            'logo_path' => $path,
            'logo_url'  => $url,
        ]);
        $setting->update(['data' => $data]);

        AuditLogger::log('settings', 'settings.logo_updated', 'Logo da empresa atualizada', [
            'subject' => $setting,
        ]);

        return response()->json(['logo_url' => $url]);
    }

    private function defaults(): array
    {
        return [
            'nome_empresa'             => '',
            'cnpj'                     => '',
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
