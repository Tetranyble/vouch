<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FreshInstallSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_authentication_uses_only_native_laravel_columns(): void
    {
        foreach (['password', 'email_verified_at', 'remember_token', 'google_id', 'account_id', 'deleted_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), $column.' missing');
        }
        foreach (['encrypted_password','reset_password_token','reset_password_sent_at',
            'remember_created_at','confirmed_at','confirmation_token','confirmation_sent_at',
            'unconfirmed_email','sign_in_count','failed_attempts','unlock_token','locked_at',
            'otp_secret','otp_required_for_login','consumed_timestep','archived_at'] as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column), $column.' should not exist');
        }
    }

    #[Test]
    public function old_rails_infrastructure_tables_are_absent(): void
    {
        foreach (['access_tokens','oauth_applications','oauth_access_grants','oauth_access_tokens',
            'active_storage_blobs','active_storage_attachments','active_storage_variant_records',
            'console1984_users','console1984_sessions','console1984_sensitive_accesses',
            'console1984_commands','lock_events','account_accesses','account_linked_accounts'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table.' should not exist');
        }
        foreach (['users','sessions','password_reset_tokens','personal_access_tokens'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table.' missing');
        }
    }

    #[Test]
    public function password_is_hashed_at_model_boundary_and_hidden_from_serialization(): void
    {
        $user = User::factory()->create(['password' => 'a-real-password']);
        $this->assertTrue(Hash::check('a-real-password', $user->fresh()->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertTrue($user->hasVerifiedEmail());
    }
}
