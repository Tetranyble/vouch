<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Submission;
use App\Models\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function paymentSigner(): Submitter
    {
        $submission = Submission::factory()->create();
        $submitter = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);
        $submission->update(['template_fields' => [[
            'uuid' => 'payment-field',
            'submitter_uuid' => $submitter->uuid,
            'type' => 'payment',
            'name' => 'Document fee',
            'preferences' => ['price' => 12.50, 'currency' => 'USD'],
        ]]]);
        return $submitter;
    }

    private function signedEvent(Submitter $submitter, int $timestamp, int $amount = 1250): array
    {
        $body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'mode' => 'payment',
                'payment_status' => 'paid',
                'amount_total' => $amount,
                'currency' => 'usd',
                'payment_intent' => 'pi_verified_123',
                'metadata' => [
                    'submitter_slug' => $submitter->slug,
                    'field_uuid' => 'payment-field',
                    'account_id' => (string) $submitter->account_id,
                ],
            ]],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test');
        return [$body, "t={$timestamp},v1={$signature}"];
    }

    #[Test]
    public function webhook_requires_a_configured_secret_and_valid_signature(): void
    {
        $submitter = $this->paymentSigner();
        config()->set('services.stripe.webhook_secret', null);
        $this->postJson('/stripe/webhook', ['type' => 'checkout.session.completed'])->assertStatus(503);
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        [$body] = $this->signedEvent($submitter, now()->timestamp);
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
            ->assertStatus(400);
        $this->assertSame([], $submitter->fresh()->values ?? []);
    }

    #[Test]
    public function only_recent_paid_matching_checkout_sessions_change_payment_fields(): void
    {
        $submitter = $this->paymentSigner();
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        [$body, $signature] = $this->signedEvent($submitter, now()->timestamp);
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature];
        $this->call('POST', '/stripe/webhook', [], [], [], $headers, $body)->assertOk();
        $this->assertSame('pi_verified_123', $submitter->fresh()->values['payment-field']);
        $this->call('POST', '/stripe/webhook', [], [], [], $headers, $body)->assertOk();
        $this->assertSame('pi_verified_123', $submitter->fresh()->values['payment-field']);

        [$differentAmountBody, $differentAmountSignature] = $this->signedEvent($submitter, now()->timestamp, 999);
        $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $differentAmountSignature,
        ], $differentAmountBody)->assertOk();
        $this->assertSame('pi_verified_123', $submitter->fresh()->values['payment-field']);

        [$oldBody, $oldSignature] = $this->signedEvent($submitter, now()->subMinutes(6)->timestamp);
        $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $oldSignature,
        ], $oldBody)->assertStatus(400);
    }

    #[Test]
    public function payment_session_uses_server_controlled_amount_not_browser_supplied_price(): void
    {
        $submitter = $this->paymentSigner();
        config()->set('services.stripe.secret', 'sk_test_secret');
        Http::fake(['api.stripe.com/*' => Http::response(['url' => 'https://checkout.stripe.com/c/test'], 200)]);
        $this->postJson('/s/'.$submitter->slug.'/stripe-session', [
            'field_uuid' => 'payment-field',
            'price_id' => 'price_from_browser_is_ignored',
        ])->assertOk();
        Http::assertSent(fn ($request) => $request['line_items[0][price_data][unit_amount]'] === 1250
            && ! isset($request['line_items[0][price]']));
    }
}
