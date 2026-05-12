<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Verification;
use App\Services\AccountVerificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private AccountVerificationService $accountVerification) {}

    /**
     * Handle a registration request.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $referredById = null;
        if (! empty($validated['referred_by_code'] ?? null)) {
            $referredBy = User::where('referral_code', $validated['referred_by_code'])->first();
            if ($referredBy) {
                $referredById = $referredBy->id;
                $referredBy->increment('points', setting('referral_points', 0));
            }
        }
        $user = User::create([
            'name' => $validated['name'],
            'country_id' => $validated['country_id'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
            'is_verified' => false,
            'referred_by_id' => $referredById,
            'gender' => $validated['gender'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'national_number' => $validated['national_number'] ?? null,
            'national_id_expire_date' => $validated['national_id_expire_date'] ?? null,
            'home_address' => $validated['home_address'] ?? null,
        ]);
        $user->assignRole('user');
        $this->accountVerification->sendForNewUser($user);
        event(new Registered($user));

        // Load roles and permissions for the response
        $user->load('roles', 'permissions', 'country');

        return response()->json([
            'success' => true,
            'message' => __('Registration successful. Please verify your account before logging in.'),
            'data' => [
                'user' => new UserResource($user),
            ],
        ], 201);
    }

    /**
     * Handle a login request to the application.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if (! $user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => __('Account not verified. Please verify your account before logging in.'),
            ], 401);
        }

        // Load roles and permissions for the response
        $user->load('roles', 'permissions', 'country');

        // Revoke all existing tokens (optional - for single device login)
        // $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('Login successful.'),
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => __('Logged out successfully.'),
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles', 'permissions', 'country');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Resend verification code.
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['sometimes', 'nullable', 'email', 'exists:users,email'],
            'phone' => ['sometimes', 'nullable', 'string'],
            'country_id' => ['sometimes', 'nullable', 'exists:countries,id'],
        ]);

        $user = null;
        if (! empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        } elseif (! empty($data['phone'])) {
            $query = User::where('phone', $data['phone']);
            if (! empty($data['country_id'])) {
                $query->where('country_id', $data['country_id']);
            }
            $user = $query->first();
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('User not found.'),
            ], 404);
        }

        try {
            $this->accountVerification->resend($user);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('Verification code resent successfully.'),
        ]);
    }

    /**
     * Verify the user's account.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('User not found.'),
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => __('Account already verified.'),
            ], 400);
        }

        $verification = Verification::where('user_id', $user->id)->where('type', 'email')->where('code', $data['code'])->first();
        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid verification code.'),
            ], 400);
        }

        $user->is_verified = true;
        $user->email_verified_at = now();
        $user->save();

        $verification->delete();

        // Create a new token
        $token = $user->createToken('auth-token', ['*'])->plainTextToken;

        $user->load('roles', 'permissions', 'country');

        return response()->json([
            'success' => true,
            'message' => __('Account verified successfully.'),
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Verify the user's phone number.
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);
        $user = User::where('phone', $data['phone'])->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('User not found.'),
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => __('Account already verified.'),
            ], 400);
        }

        $verification = Verification::query()
            ->where('user_id', $user->id)
            ->where('type', 'phone')
            ->where('target', $data['phone'])
            ->where('code', $data['code'])
            ->valid()
            ->first();
        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid verification code.'),
            ], 400);
        }

        $user->is_verified = true;
        $user->phone_verified_at = now();
        $user->save();

        $verification->markAsVerified();

        // Create a new token
        $token = $user->createToken('auth-token', ['*'])->plainTextToken;

        $user->load('roles', 'permissions', 'country');

        return response()->json([
            'success' => true,
            'message' => __('Account verified successfully.'),
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }
}
