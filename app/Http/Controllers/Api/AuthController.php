<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyAdminOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminLoginOtpService;
use App\Support\PasswordRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AdminLoginOtpService $adminLoginOtpService,
    ) {}
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'prenom' => $request->prenom,
            'nom' => $request->nom,
            'email' => $request->email,
            'mot_de_passe' => Hash::make($request->password),
            'est_confirme' => false,
            'est_actif' => true,
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'message' => 'Compte créé. Un email de vérification a été envoyé.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->mot_de_passe)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        if ((int) $user->est_actif !== 1) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        if ($user->bloquer) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        if ((bool) $user->is_admin) {
            $challenge = $this->adminLoginOtpService->issue($user);

            return response()->json([
                'data' => [
                    'requires_otp' => true,
                    'challenge_token' => $challenge->challenge_token,
                    'expires_at' => $challenge->expires_at->toIso8601String(),
                ],
                'message' => 'Un code de vérification a été envoyé à votre adresse e-mail.',
            ]);
        }

        $user->update(['derniere_connexion' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function verifyAdminOtp(VerifyAdminOtpRequest $request): JsonResponse
    {
        try {
            $user = $this->adminLoginOtpService->verify(
                $request->string('challenge_token')->toString(),
                $request->string('code')->toString(),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ((int) $user->est_actif !== 1) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        if ($user->bloquer) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $user->update(['derniere_connexion' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'message' => 'Connexion administrateur réussie.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => PasswordRules::required(),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'mot_de_passe' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
        }

        return response()->json(['message' => __($status)], 422);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required_without:email', 'integer'],
            'email' => ['required_without:id', 'email'],
            'token' => ['required', 'string'],
        ]);

        $user = $request->filled('id')
            ? User::find($request->integer('id'))
            : User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Lien de vérification invalide.'], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.']);
        }

        $token = (string) $request->token;
        $tokenMatches = $user->token_confirmation
            && hash_equals((string) $user->token_confirmation, $token);

        if ($tokenMatches && $user->isEmailVerificationTokenExpired()) {
            return response()->json([
                'message' => 'Ce lien de confirmation a expiré. Demandez un nouvel email de vérification.',
                'expired' => true,
            ], 422);
        }

        if (! $user->isEmailVerificationTokenValid($token)) {
            return response()->json(['message' => 'Lien de vérification invalide.'], 422);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email vérifié avec succès.']);
    }

    public function resendVerificationByEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required_without:id', 'email'],
            'id' => ['required_without:email', 'integer'],
        ]);

        $user = $request->filled('id')
            ? User::find($request->integer('id'))
            : User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Si cette adresse est associée à un compte non confirmé, un email vient d\'être envoyé.',
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email de vérification renvoyé.']);
    }
}
