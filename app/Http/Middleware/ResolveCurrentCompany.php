<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;

class ResolveCurrentCompany
{
    /**
     * Reads the X-Company-Id header sent by the frontend and validates that the authenticated
     * user actually belongs to that company before trusting it — this is the tenant-isolation
     * boundary between companies, so the membership check must never be skipped.
     */
    public function handle(Request $request, Closure $next)
    {
        $companyId = $request->header('X-Company-Id');

        abort_if(!$companyId, 400, 'Nenhuma empresa selecionada.');

        $membership = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_if(!$membership, 403, 'Você não tem acesso a essa empresa.');

        $company = Company::find($companyId);

        abort_if(!$company, 404);

        $request->attributes->set('company', $company);
        $request->attributes->set('companyMembership', $membership);

        return $next($request);
    }
}
