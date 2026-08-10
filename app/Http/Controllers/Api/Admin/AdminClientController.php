<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Models\Asset;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Generation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdminClientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(EnsurePlatformAdmin::class),
        ];
    }

    public function index(Request $request)
    {
        $companies = Company::with('owners')
            ->withCount(['memberships as members_count' => fn ($q) => $q->where('is_owner', false)])
            ->latest()
            ->get();

        $pricing = config('ai_pricing');

        $result = $companies->map(function (Company $company) use ($pricing) {
            $images = Asset::where('company_id', $company->id)->where('type', 'image')->count();
            $videos = Asset::where('company_id', $company->id)->where('type', 'video')->count();
            $texts  = Generation::where('company_id', $company->id)->where('type', 'text')->where('status', 'completed')->count();

            return [
                'id' => $company->id,
                'company_name' => $company->name,
                'company_cnpj' => $company->cnpj,
                'owner_name' => $company->owners->pluck('name')->join(', ') ?: '—',
                'email' => $company->owners->pluck('email')->join(', ') ?: '—',
                'created_at' => $company->created_at,
                'members_count' => $company->members_count,
                'campaigns_count' => Campaign::where('company_id', $company->id)->count(),
                'usage' => ['image' => $images, 'video' => $videos, 'text' => $texts],
                'estimated_ai_cost' => round($images * $pricing['image'] + $videos * $pricing['video'] + $texts * $pricing['text'], 2),
                'monthly_fee' => $company->monthly_fee,
            ];
        });

        return response()->json($result->values());
    }

    public function update(Request $request, Company $client)
    {
        $data = $request->validate([
            'monthly_fee' => 'nullable|numeric|min:0',
        ]);

        $client->forceFill($data)->save();

        AuditLogger::log('billing', 'client.monthly_fee_updated', "Mensalidade de \"{$client->name}\" atualizada para R$ " . number_format($data['monthly_fee'] ?? 0, 2, ',', '.'), [
            'company_id' => $client->id,
            'causer_id' => $request->user()->id,
            'input' => $data,
        ]);

        return response()->json(['id' => $client->id, 'monthly_fee' => $client->monthly_fee]);
    }
}
