<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\SqliteOtpCode;
use App\Models\SqliteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_is_emailed_and_only_its_hash_is_stored(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/otp/request', ['email' => ' Movie.Buddy@example.com '])
            ->assertAccepted()
            ->assertJsonMissingPath('code');

        $otp = SqliteOtpCode::query()->firstOrFail();
        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->hasTo('movie.buddy@example.com');
        });

        $this->assertSame('movie.buddy@example.com', $otp->email);
        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(Hash::check($code, $otp->code_hash));
        $this->assertTrue($otp->expires_at->between(now()->addMinutes(9), now()->addMinutes(11)));
        $this->assertDatabaseCount('otp_codes', 1);
    }

    public function test_otp_requests_are_limited_to_three_per_hour(): void
    {
        Mail::fake();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/auth/otp/request', ['email' => 'limit@example.com'])
                ->assertAccepted();
        }

        $this->postJson('/api/auth/otp/request', ['email' => 'limit@example.com'])
            ->assertTooManyRequests();

        Mail::assertSent(OtpCodeMail::class, 3);
    }

    public function test_five_incorrect_codes_are_rate_limited(): void
    {
        Mail::fake();
        $email = 'wrong-code@example.com';

        $code = $this->requestOtp($email);
        $incorrectCode = $code === '000000' ? '000001' : '000000';

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/auth/otp/verify', [
                'email' => $email,
                'code' => $incorrectCode,
            ]);

            $response->assertStatus($attempt === 5 ? 429 : 422);
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_expired_code_cannot_register(): void
    {
        Mail::fake();
        $email = 'expired@example.com';
        $code = $this->requestOtp($email);
        SqliteOtpCode::query()->where('email', $email)->update(['expires_at' => now()->subSecond()]);

        $this->postJson('/api/auth/otp/verify', $this->registrationPayload($email, $code))
            ->assertUnprocessable()
            ->assertJsonPath('error', 'otp_invalid_or_expired');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_expired_codes_can_be_removed_by_the_retention_command(): void
    {
        Mail::fake();
        $email = 'purge@example.com';
        $this->requestOtp($email);
        SqliteOtpCode::query()->where('email', $email)->update(['expires_at' => now()->subSecond()]);

        $this->artisan('otp:purge')->expectsOutput('Removed 1 expired OTP record(s).')->assertSuccessful();

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_registration_rejects_people_under_eighteen(): void
    {
        Mail::fake();
        $email = 'minor@example.com';
        $code = $this->requestOtp($email);
        $payload = $this->registrationPayload($email, $code);
        $payload['birth_date'] = now('Asia/Ho_Chi_Minh')->subYears(17)->toDateString();

        $this->postJson('/api/auth/otp/verify', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('birth_date');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('consents', 0);
        $this->assertDatabaseCount('otp_codes', 1);
    }

    public function test_registration_accepts_a_user_who_is_exactly_eighteen(): void
    {
        Mail::fake();
        $email = 'eighteen@example.com';
        $code = $this->requestOtp($email);
        $payload = $this->registrationPayload($email, $code);
        $payload['birth_date'] = now('Asia/Ho_Chi_Minh')->subYears(18)->toDateString();

        $this->postJson('/api/auth/otp/verify', $payload)
            ->assertOk()
            ->assertJsonPath('user.email', $email);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_successful_registration_records_required_consents_and_returns_a_sanctum_token(): void
    {
        Mail::fake();
        $email = 'new-user@example.com';
        $code = $this->requestOtp($email);

        $response = $this->postJson('/api/auth/otp/verify', $this->registrationPayload($email, $code))
            ->assertOk()
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('user.display_name', 'Minh Anh')
            ->assertJsonPath('user.age_verify_method', 'self_declared')
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'display_name']]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('consents', 2);
        $this->assertDatabaseHas('consents', ['type' => 'terms', 'granted' => true]);
        $this->assertDatabaseHas('consents', ['type' => 'privacy', 'granted' => true]);
        $this->assertDatabaseCount('otp_codes', 0);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => SqliteUser::query()->value('id'),
            'tokenable_type' => SqliteUser::class,
        ]);

        $this->withToken($response->json('token'))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', $email)
            ->assertJsonMissingPath('user.rating')
            ->assertJsonMissingPath('user.stats');

        $this->withToken($response->json('token'))
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();

        $this->withToken($response->json('token'))
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_existing_user_can_sign_in_without_resubmitting_registration_details(): void
    {
        Mail::fake();
        $email = 'returning@example.com';
        $user = SqliteUser::query()->create([
            'email' => $email,
            'display_name' => 'Existing Member',
            'birth_date' => '1995-01-01',
        ]);
        $user->forceFill([
            'email_verified_at' => now(),
            'age_verified_at' => now(),
            'age_verify_method' => 'self_declared',
            'role' => 'user',
            'status' => 'active',
        ])->save();
        $code = $this->requestOtp($email);

        $this->postJson('/api/auth/otp/verify', ['email' => $email, 'code' => $code])
            ->assertOk()
            ->assertJsonPath('user.display_name', 'Existing Member');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('consents', 0);
    }

    private function requestOtp(string $email): string
    {
        $this->postJson('/api/auth/otp/request', ['email' => $email])->assertAccepted();

        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);
        $this->assertTrue(Hash::check($code, SqliteOtpCode::query()->where('email', $email)->value('code_hash')));

        return $code;
    }

    private function registrationPayload(string $email, string $code): array
    {
        return [
            'email' => $email,
            'code' => $code,
            'display_name' => 'Minh Anh',
            'birth_date' => now('Asia/Ho_Chi_Minh')->subYears(25)->toDateString(),
            'consents' => ['terms' => true, 'privacy' => true],
        ];
    }
}
