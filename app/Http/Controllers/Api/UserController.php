<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    public function login(Request $request)
    {
		// return response()->json(['message' => 'Login successful', 'user' => User::all()], 200);
        // Validate the incoming request
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt to find the user by email
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            // Store user ID in session
            Session::put('user_id', $user->id);
            return response()->json(['message' => 'Login successful', "status"=> true, 'user' => $user], 200);
        } else {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }
    }

    public function register(Request $request)
    {
        try {
					
            // Validate the incoming request
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|lowercase|max:255|unique:users',
                
            ]);

            // Create a new user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);


            return response()->json(["message" => 'Registration successful', "status"=> true, "user" => $user], 200);
        } catch (\Exception $e) {
            return response()->json(["error" => 'Email already exists or is not valid', "status"=> true], 400);
        }
    }

    public function forgotpassword(Request $request)
    {
        // Validate the incoming request
        $request->validate(['email' => 'required|email']);

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent',"status"=> true], 200);
        } else {
            return response()->json(['error' => 'Unable to send reset link'], 400);
        }
    }

    public function resetpassword(Request $request)
    {

			
			// Validate the incoming request
			$request->validate([
				
				'email' => 'required|email',
				'password' => 'required|string|',
			]);
			$user = User::where('email', $request->email)->first();
			
			$user->update([
				'password' => Hash::make($request->password),
				]);
				$user->save();
				
			


        // // Reset the user's password
        // $status = Password::reset(
        //     $request->only('email', 'password', 'password_confirmation'),
        //     function ($user, $password) {
        //         $user->forceFill([
        //             'password' => Hash::make($password)
        //         ])->save();                
        //     }
        // );

        // if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully', "status"=> true], 200);
        // } else {
        //     return response()->json(['error' => 'Unable to reset password'], 400);
        // }
    }

    public function logout(Request $request)
    {
        // Clear the session
        Session::forget('user_id');
        Session::regenerate();

        return response()->json(['message' => 'Logout successful', "status"=> true], 200);
    }

    // Method to protect routes
    public function checkAuth(Request $request)
    {
			return response()->json(['authenticated' =>  'user', "status"=> true], 200);
        // if (Session::has('user_id')) {
        //     $user = User::find(Session::get('user_id'));
        // } else {
        //     return response()->json(['authenticated' => false], 401);
        // }
    }

    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json($user, 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
