<?php

namespace Tests\Feature;

use App\Jobs\ProcessGeneration;
use App\Models\Action;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActionGenerateQuantityTest extends TestCase
{
    use RefreshDatabase;

    private function action(): array
    {
        $company = Company::create(['name' => 'Mercado Teste']);
        $user = User::factory()->create(['is_client' => true]);
        CompanyUser::create(['company_id' => $company->id, 'user_id' => $user->id, 'is_owner' => true]);
        $campaign = Campaign::create(['company_id' => $company->id, 'name' => 'Promo', 'status' => 'draft']);
        $action = Action::create([
            'campaign_id' => $campaign->id, 'company_id' => $company->id,
            'title' => 'Post', 'type' => 'post', 'platform' => 'instagram', 'status' => 'draft', 'quantity' => 4,
        ]);

        Sanctum::actingAs($user);

        return [$company, $action];
    }

    public function test_quantity_picked_in_the_generator_is_what_the_job_runs_with(): void
    {
        Queue::fake();
        [$company, $action] = $this->action();

        $this->postJson("/api/actions/{$action->id}/generate", ['type' => 'image', 'quantity' => 1], ['X-Company-Id' => $company->id])
            ->assertStatus(202);

        $this->assertSame(1, $action->fresh()->quantity);
        Queue::assertPushed(ProcessGeneration::class);
    }

    public function test_omitting_quantity_keeps_the_action_value(): void
    {
        Queue::fake();
        [$company, $action] = $this->action();

        $this->postJson("/api/actions/{$action->id}/generate", ['type' => 'text'], ['X-Company-Id' => $company->id])
            ->assertStatus(202);

        $this->assertSame(4, $action->fresh()->quantity);
    }
}
