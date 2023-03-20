<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class userscontroller extends Controller
{
    //

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Register user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|string|email|max:100|unique:users',
        'password' => 'required|string|min:6',
        'dob' => 'required|integer|min:2|max:120',
        'location' => 'required|string|min:2|max:100',
        'gender' => 'required|integer',
        'bio' => 'required|string|min:2|max:1000',
        'profile_picture' => 'required|string'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'location' => $request->location,
        'dob' => $request->dob,
        'gender' => $request->gender,
        'bio' => $request->bio,
    ]);

    if ($request->profile_picture) {
        $encoded = $request->profile_picture;
        $id = $user->id;

        $decoded = base64_decode($encoded);

        $file_path = public_path('images/' . $id . '.png');

        file_put_contents($file_path, $decoded);

        $user->pic = 'http://localhost/images/' . $id . '.png';
        $user->save();
    }

    return response()->json([
        'message' => 'User successfully registered',
        'user' => $user
    ], 201);
}


    /**
     * login user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user_id = auth()->user()->id;
        return $this->respondWithTokenAndId($token, $user_id);
    }

    private function respondWithTokenAndId($token, $user_id)
{
    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'user_id' => $user_id,
    ]);
}



    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully logged out.']);
    }

    /**
     * Refresh token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get user profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}

