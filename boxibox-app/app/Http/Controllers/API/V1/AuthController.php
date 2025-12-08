<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @tags Authentication
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new user account and returns an API token.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     *
     * @response 201 {
     *   "message": "User registered successfully",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "status": "active"
     *   },
     *   "token": "1|abc123..."
     * }
     * @response 422 {
     *   "message": "The email has already been taken.",
     *   "errors": {"email": ["The email has already been taken."]}
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        // Assign default client role
        $user->assignRole('client');

        // Create API token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     *
     * Authenticates a user and returns an API token for subsequent requests.
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response 200 {
     *   "message": "Login successful",
     *   "user": {
     *     "id": 1,
     *     "tenant_id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "status": "active",
     *     "roles": ["admin"]
     *   },
     *   "token": "1|abc123..."
     * }
     * @response 422 {
     *   "message": "The provided credentials are incorrect.",
     *   "errors": {"email": ["The provided credentials are incorrect."]}
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // Check if user is active
        if ($user->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        // Create API token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     *
     * Revokes the current API token, effectively logging out the user.
     *
     * @response 200 {
     *   "message": "Logout successful"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current user's token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get authenticated user
     *
     * Returns the currently authenticated user's information including roles and permissions.
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "tenant_id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "phone": "+33612345678",
     *     "avatar": null,
     *     "status": "active",
     *     "roles": ["admin"],
     *     "permissions": ["view-sites", "manage-boxes"]
     *   }
     * }
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
