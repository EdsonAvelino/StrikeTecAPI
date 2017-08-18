<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class UserController extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|max:64|unique:users',
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[ A-Za-z0-9_@.#&+-]{6,}$/',
        ]);

        // Creates a new user
        $user = User::create([
            'email' => $request->get('email'),
            'password' => app('hash')->make($request->get('password'))
        ]);
        
        try {
            if (! $token = $this->jwt->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'ture', 'message' => 'Invalid credentials user is not registered']);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'user' => \Auth::user()]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'gender' => 'in:male,female',
            'birthday' => 'date',
        ]);

        try {
            $user = \Auth::user();
            $user->first_name = $request->get('first_name');
            $user->last_name = $request->get('last_name');
            $user->gender = $request->get('gender');
            $user->birthday = date('Y-m-d', strtotime($request->get('birthday')));
            $user->weight = $request->get('weight');
            $user->height = $request->get('height');
            $user->stance = $request->get('stance');

            $user->save();
            return null;

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => $e->getMessage()
            ]);
        }
    }
}