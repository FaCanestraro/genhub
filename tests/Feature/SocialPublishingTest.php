<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Generation;
use App\Models\Publication;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\MetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SocialPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): array
    {
        $company = Company::create(['name' => 'Acme']);
        $user = User::factory()->create();
        $user->forceFill(['is_client' => true])->save();
        CompanyUser::create(['company_id' => $company->id, 'user_id' => $user->id, 'is_owner' => true]);

        return [$company, $user];
    }

    private function state(int $companyId, int $userId): string
    {
        return Crypt::encryptString(json_encode(['company_id' => $companyId, 'user_id' => $userId, 'exp' => now()->addMinutes(5)->timestamp]));
    }

    public function test_callback_stores_page_and_linked_instagram_and_rejects_foreign_state(): void
    {
        [$company, $user] = $this->owner();
        [, $other] = $this->owner();

        Http::fake([
            '*/oauth/access_token*' => Http::response(['access_token' => 'tok']),
            '*/me/accounts*' => Http::response(['data' => [[
                'id' => 'p1', 'name' => 'Página Acme', 'access_token' => 'page-tok',
                'instagram_business_account' => ['id' => 'ig1', 'username' => 'acme'],
            ]]]),
        ]);

        $headers = ['X-Company-Id' => $company->id];

        $this->actingAs($other)->postJson('/api/social-accounts/callback', ['code' => 'c', 'state' => $this->state($company->id, $user->id)], $headers)
            ->assertForbidden();

        $this->actingAs($user)->postJson('/api/social-accounts/callback', ['code' => 'c', 'state' => $this->state($company->id, $user->id)], $headers)
            ->assertOk()->assertJson(['connected' => 2]);

        $this->assertEqualsCanonicalizing(['facebook', 'instagram'], SocialAccount::pluck('provider')->all());
        $this->assertSame('page-tok', SocialAccount::where('provider', 'instagram')->first()->access_token);
    }

    public function test_instagram_carousel_converts_png_to_jpeg_and_publishes(): void
    {
        Storage::fake('r2');
        Sleep::fake();
        [$company] = $this->owner();

        $img = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($img);
        Storage::disk('r2')->put('a.png', ob_get_clean());
        Storage::disk('r2')->put('b.jpg', 'jpeg-bytes');

        $generation = Generation::create(['company_id' => $company->id, 'type' => 'carousel', 'status' => 'completed']);
        $png = Asset::create(['generation_id' => $generation->id, 'company_id' => $company->id, 'type' => 'image', 'disk' => 'r2', 'path' => 'a.png', 'mime_type' => 'image/png']);
        $jpg = Asset::create(['generation_id' => $generation->id, 'company_id' => $company->id, 'type' => 'image', 'disk' => 'r2', 'path' => 'b.jpg', 'mime_type' => 'image/jpeg']);
        $account = SocialAccount::create(['company_id' => $company->id, 'provider' => 'instagram', 'external_id' => 'ig1', 'name' => 'acme', 'access_token' => 't']);

        $created = 0;
        Http::fake(function ($request) use (&$created) {
            $url = $request->url();
            if (str_ends_with($url, '/ig1/media')) return Http::response(['id' => 'c' . ++$created]);
            if (str_ends_with($url, '/ig1/media_publish')) return Http::response(['id' => 'm1']);
            if (str_contains($url, 'fields=permalink')) return Http::response(['permalink' => 'https://instagram.com/p/x']);
            return Http::response(['status_code' => 'FINISHED']);
        });

        $publication = Publication::create([
            'company_id' => $company->id, 'social_account_id' => $account->id, 'generation_id' => $generation->id,
            'asset_ids' => [$png->id, $jpg->id], 'caption' => 'Oi',
        ]);

        $result = app(MetaService::class)->publish($publication);

        $this->assertSame(['id' => 'm1', 'permalink' => 'https://instagram.com/p/x'], $result);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/ig1/media') && ($r['media_type'] ?? null) === 'CAROUSEL' && $r['children'] === 'c1,c2');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/ig1/media') && str_ends_with($r['image_url'] ?? '', '.jpg') && str_contains($r['image_url'], 'social-tmp/'));
        $this->assertSame([], Storage::disk('r2')->files('social-tmp'), 'JPEG temporário deve ser apagado após publicar');
    }
}
