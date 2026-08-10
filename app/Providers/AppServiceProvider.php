<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\CompanyUser;
use Gemini\Contracts\ClientContract;
use Gemini\Factory as GeminiFactory;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Company currently active for this request, resolved by the ResolveCurrentCompany
        // middleware from the X-Company-Id header. Only set on company-scoped routes.
        Request::macro('company', function (): ?Company {
            return $this->attributes->get('company');
        });

        Request::macro('companyMembership', function (): ?CompanyUser {
            return $this->attributes->get('companyMembership');
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $caBundle = $this->findCaBundle();

        if (!$caBundle) {
            return;
        }

        Http::globalOptions(['verify' => $caBundle]);

        // extend() survives deferred provider re-registration and runs post-resolution
        $this->app->extend(ClientContract::class, function () use ($caBundle) {
            return (new GeminiFactory())
                ->withApiKey(config('gemini.api_key'))
                ->withHttpClient(new GuzzleClient(['verify' => $caBundle, 'timeout' => 60]))
                ->make();
        });
    }

    private function findCaBundle(): string|false
    {
        $candidates = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:/Users/fabio/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe/cacert.pem',
        ];

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return false;
    }
}
