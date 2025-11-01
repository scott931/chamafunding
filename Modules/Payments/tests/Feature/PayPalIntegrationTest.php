<?php

namespace Modules\Payments\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class PayPalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_access_checkout_page()
    {
        $response = $this->get('/checkout');

        $response->assertStatus(200);
        $response->assertViewIs('payments::checkout');
    }

    /** @test */
    public function it_can_access_checkout_with_parameters()
    {
        $response = $this->get('/checkout?amount=25.00&currency=USD&description=Test Payment');

        $response->assertStatus(200);
        $response->assertViewIs('payments::checkout');
        $response->assertViewHas('amount', 25.00);
        $response->assertViewHas('currency', 'USD');
        $response->assertViewHas('description', 'Test Payment');
    }

    /** @test */
    public function it_can_access_success_page()
    {
        $response = $this->get('/checkout/success?order_id=test123&amount=25.00&currency=USD');

        $response->assertStatus(200);
        $response->assertViewIs('payments::checkout-success');
        $response->assertViewHas('orderId', 'test123');
        $response->assertViewHas('amount', 25.00);
        $response->assertViewHas('currency', 'USD');
    }

    /** @test */
    public function it_requires_authentication_for_api_endpoints()
    {
        $response = $this->postJson('/api/v1/paypal/orders', [
            'amount' => 10.00,
            'currency' => 'USD',
            'description' => 'Test',
            'return_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_order_creation_parameters()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/paypal/orders', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount', 'currency', 'return_url', 'cancel_url']);
    }

    /** @test */
    public function it_validates_capture_order_parameters()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/paypal/orders/test123/capture', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id']);
    }

    /** @test */
    public function it_validates_get_order_parameters()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/paypal/orders/');

        $response->assertStatus(404); // Route not found without order_id
    }

    /** @test */
    public function webhook_endpoint_accepts_post_requests()
    {
        $response = $this->postJson('/api/v1/paypal/webhook', [
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'id' => 'test-webhook-id',
            'resource' => [
                'id' => 'test-order-id'
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }
}
