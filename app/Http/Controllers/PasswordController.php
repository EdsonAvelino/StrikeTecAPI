<?php

namespace App\Http\Controllers;

use App\User;
use App\PasswordResets;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetCodeEmail;


class PasswordController extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function postEmail(Request $request)
    {
        $user = User::where('email', $request->get('email'))->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid request, User not found.'], 204);
        } else {
            $code = sprintf("%06d", mt_rand(1, 999999));

            $object = PasswordResets::create([
                'user_id' => $user->id,
                'code' => $code
            ]);

            Mail::to($user)->send(new PasswordResetCodeEmail($user, $code));

            $payload = JWTFactory::sub($user->id)
                        // ->exp(strtotime($object->expires_at))
                        ->key($object->key)
                        ->email($user->email)
                        ->make();
            
            $token = $this->jwt->encode($payload)->get();

            return response()->json(['token' => $token]);
        }
    }

    public function postVerifyCode(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|numeric',
        ]);

        $code = $request->get('code');

        $token = $this->jwt->parseToken();
        
        $userId = $token->getClaim('sub');
        $key = $token->getClaim('key');
        
        $object = PasswordResets::where('key', $key)->where('user_id', $userId)->first();
        
        if (!$object || $object->code != $code) {
            return response()->json(['error' => 'Invalid code']);
        } else {
            $object->delete();
            $payload = JWTFactory::sub($userId)
                        // ->exp(strtotime($object->expires_at))
                        ->verified(1)
                        ->make();
            
            $token = $this->jwt->encode($payload)->get();
            return response()->json(['success' => 'true']);
        }

        return null;
    }

    public function postReset(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|min:10|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{10,}$/',
        ]);

        $token = $this->jwt->parseToken();
        
        $userId = $token->getClaim('sub');
        $verified = $token->getClaim('verified');

        if ($verified) {
            $user = User::find($userId)
                ->update(['password' => app('hash')->make($request->get('password'))]);

            return response()->json(['success' => 'true']);
        }
    }
}
