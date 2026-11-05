<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Mail\OtpCodeMail;
use App\Support\AuthModels;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $key = hash('sha256', $email);
        $rateKey = "otp-request:{$key}";

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return $this->rateLimited();
        }

        RateLimiter::hit($rateKey, 3600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otpModel = AuthModels::otpCode();

        $otpModel::query()->updateOrCreate(
            ['email' => $email],
            ['code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10)],
        );

        try {
            Mail::to($email)->send(new OtpCodeMail($code));
        } catch (\Throwable $exception) {
            $otpModel::query()->where('email', $email)->delete();

            throw $exception;
        }

        return response()->json([
            'message' => 'Nếu địa chỉ email hợp lệ, mã đăng nhập sẽ được gửi trong ít phút.',
        ], 202);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $email = $data['email'];
        $rateKey = 'otp-verify:'.hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return $this->rateLimited();
        }

        $result = DB::transaction(function () use ($data, $email, $rateKey) {
            $otpModel = AuthModels::otpCode();
            $userModel = AuthModels::user();
            $consentModel = AuthModels::consent();
            $otp = $otpModel::query()->where('email', $email)->lockForUpdate()->first();

            if ($otp === null || $otp->expires_at->isPast()) {
                return ['error' => 'otp_invalid_or_expired', 'status' => 422];
            }

            if (! Hash::check($data['code'], $otp->code_hash)) {
                RateLimiter::hit($rateKey, 3600);

                if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                    return ['error' => 'otp_rate_limited', 'status' => 429];
                }

                return ['error' => 'otp_invalid_or_expired', 'status' => 422];
            }

            $user = $userModel::query()->where('email', $email)->first();

            if ($user === null) {
                $registration = Validator::make($data, [
                    'display_name' => ['required', 'string', 'min:2', 'max:40'],
                    'birth_date' => [
                        'required',
                        'date_format:Y-m-d',
                        'before_or_equal:'.now('Asia/Ho_Chi_Minh')->subYears(18)->toDateString(),
                    ],
                    'consents.terms' => ['required', 'accepted'],
                    'consents.privacy' => ['required', 'accepted'],
                ])->validate();

                $user = new $userModel;
                $user->email = $email;
                $user->display_name = trim($registration['display_name']);
                $user->birth_date = $registration['birth_date'];
                $user->email_verified_at = now();
                $user->age_verified_at = now();
                $user->age_verify_method = 'self_declared';
                $user->role = 'user';
                $user->status = 'active';
                $user->rating = ['sum' => 0, 'count' => 0];
                $user->stats = [
                    'completed_count' => 0,
                    'accepted_count' => 0,
                    'cancel_after_accept_count' => 0,
                    'sos_count' => 0,
                ];
                $user->save();

                foreach (['terms', 'privacy'] as $type) {
                    $consentModel::query()->create([
                        'user_id' => $user->id,
                        'type' => $type,
                        'version' => config("movie-buddy.consent_versions.{$type}"),
                        'granted' => true,
                        'created_at' => now(),
                    ]);
                }
            }

            if ($user->status !== 'active') {
                return ['error' => 'account_unavailable', 'status' => 403];
            }

            $user->forceFill(['email_verified_at' => now()])->save();
            $otp->delete();

            return [
                'token' => $user->createToken('movie-buddy-mobile')->plainTextToken,
                'user' => $this->publicUser($user),
            ];
        });

        if (isset($result['error'])) {
            return response()->json([
                'error' => $result['error'],
                'message' => $this->errorMessage($result['error']),
            ], $result['status']);
        }

        RateLimiter::clear($rateKey);

        return response()->json($result);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->publicUser($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    private function publicUser(Authenticatable $user): array
    {
        return [
            'id' => $user->getKey(),
            'email' => $user->email,
            'display_name' => $user->display_name,
            'age_verified_at' => $user->age_verified_at?->toISOString(),
            'age_verify_method' => $user->age_verify_method,
        ];
    }

    private function rateLimited(): JsonResponse
    {
        return response()->json([
            'error' => 'rate_limited',
            'message' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau.',
        ], 429);
    }

    private function errorMessage(string $error): string
    {
        return match ($error) {
            'otp_rate_limited' => 'Bạn đã nhập sai mã quá nhiều lần. Vui lòng thử lại sau.',
            'account_unavailable' => 'Tài khoản hiện không thể đăng nhập.',
            default => 'Mã không hợp lệ hoặc đã hết hạn.',
        };
    }
}
