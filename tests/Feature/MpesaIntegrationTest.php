<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;

class MpesaIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_initiate_mpesa_payment()
    {
        $this->actingAs($this->user, 'sanctum');

        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $response = $this->postJson('/api/v1/mpesa/initiate-payment', [
            'phone_number' => '254708374149', // Test number
            'amount' => 100.00,
            'account_reference' => 'TEST001',
            'transaction_description' => 'Test payment',
            'campaign_id' => $campaign->id
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'transaction',
                        'stk_push'
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/mpesa/initiate-payment', [
            'phone_number' => 'invalid-phone',
            'amount' => 100.00,
            'account_reference' => 'TEST001',
            'transaction_description' => 'Test payment'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number']);
    }

    /** @test */
    public function it_can_add_mpesa_payment_method()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/mpesa/payment-methods', [
            'phone_number' => '254712345678',
            'is_default' => true
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'type',
                        'provider',
                        'external_id',
                        'last_four',
                        'brand',
                        'country',
                        'is_default',
                        'is_verified'
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_can_get_mpesa_payment_methods()
    {
        $this->actingAs($this->user, 'sanctum');

        // Create a payment method first
        $this->postJson('/api/v1/mpesa/payment-methods', [
            'phone_number' => '254712345678',
            'is_default' => true
        ]);

        $response = $this->getJson('/api/v1/mpesa/payment-methods');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'provider',
                            'external_id',
                            'last_four',
                            'brand',
                            'country',
                            'is_default',
                            'is_verified'
                        ]
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_can_get_supported_countries()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/mpesa/supported-countries');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'KE' => 'Kenya',
                        'TZ' => 'Tanzania',
                        'UG' => 'Uganda'
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/mpesa/initiate-payment', [
            'phone_number' => '254708374149',
            'amount' => 100.00,
            'account_reference' => 'TEST001',
            'transaction_description' => 'Test payment'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/mpesa/initiate-payment', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'phone_number',
                    'amount',
                    'account_reference',
                    'transaction_description'
                ]);
    }
}
