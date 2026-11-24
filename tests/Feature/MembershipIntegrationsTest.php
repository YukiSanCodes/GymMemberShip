<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_requires_login(): void
    {
        $this->getJson('/weather?lat=14&lon=120')->assertUnauthorized();
    }

    public function test_weather_is_cached_and_secret_is_not_returned(): void
    {
        config(['services.openweather.key' => 'private-test-key']);
        Http::fake(['api.openweathermap.org/*' => Http::response(['name' => 'Manila', 'main' => ['temp' => 30, 'humidity' => 70], 'weather' => [['description' => 'clear']]])]);
        $this->actingAs(User::factory()->create());
        $this->getJson('/weather?lat=14.5995&lon=120.9842')->assertOk()->assertJsonPath('temperature', 30)->assertDontSee('private-test-key');
        $this->getJson('/weather?lat=14.5995&lon=120.9842')->assertOk();
        Http::assertSentCount(1);
        $this->getJson('/weather?lat=100&lon=120')->assertUnprocessable();
    }

    public function test_weather_failure_is_graceful(): void
    {
        config(['services.openweather.key' => 'test']);
        Http::fake(['*' => Http::response([], 401)]);
        $this->actingAs(User::factory()->create())->getJson('/weather?lat=14&lon=120')->assertStatus(503)->assertJsonPath('message', 'Weather is currently unavailable.');
    }

    public function test_application_saves_coordinates_and_queues_notification(): void
    {
        Notification::fake();
        $category = MembershipCategory::create(['name' => 'Regular']);
        $level = MembershipLevel::create(['name' => 'Basic', 'fee' => 1200, 'validity_months' => 1]);
        $member = Member::create(['name' => 'Ana Cruz', 'email' => 'ana@example.test', 'membership_number' => 'GYM-TEST', 'category_id' => $category->id, 'level_id' => $level->id]);
        $this->actingAs(User::factory()->create());
        $data = ['member_id' => $member->id, 'membership_level_id' => $level->id, 'type' => 'application', 'latitude' => 14.5995, 'longitude' => 120.9842];
        $this->post('/transactions', $data)->assertSessionHasErrors('location_consent');
        $this->assertDatabaseCount('membership_transactions', 0);
        $this->post('/transactions', $data + ['location_consent' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('membership_transactions', ['member_id' => $member->id, 'latitude' => 14.5995, 'status' => 'Pending']);
        Notification::assertSentOnDemand(MembershipApplicationSubmitted::class, fn ($notification) => $notification->afterCommit && $notification->connection === 'database');
    }
}
