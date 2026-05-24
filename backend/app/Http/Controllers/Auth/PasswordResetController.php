<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * 发送密码重置链接（返回 token，开发环境直接可用）
     *
     * @bodyParam email string required 注册邮箱
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式无效',
            'email.exists' => '该邮箱未注册',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            // 开发环境：从 token 表中取出 token 返回（生产环境不应返回）
            $token = \DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->value('token');

            return response()->json([
                'message' => '密码重置链接已发送',
                'reset_token' => $token, // 开发用，生产需移除
                'reset_url' => url("/reset-password/{$token}?email=" . urlencode($request->email)),
            ]);
        }

        return response()->json([
            'message' => '发送失败，请稍后重试',
        ], 500);
    }

    /**
     * 重置密码
     *
     * @bodyParam email string required 注册邮箱
     * @bodyParam token string required 重置令牌
     * @bodyParam password string required 新密码
     * @bodyParam password_confirmation string required 确认密码
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => '重置令牌不能为空',
            'email.required' => '邮箱不能为空',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少 8 个字符',
            'password.confirmed' => '两次密码输入不一致',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => '密码重置成功'])
            : response()->json(['message' => '重置令牌无效或已过期'], 400);
    }
}
