<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);
    }

    public function test_发送重置链接需要有效邮箱(): void
    {
        $response = $this->postJson('/forgot-password', [
            'email' => 'not-exists@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_发送重置链接返回_token(): void
    {
        $response = $this->postJson('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'reset_token', 'reset_url']);
    }

    public function test_重置密码需要有效_token(): void
    {
        $response = $this->postJson('/reset-password', [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
    }

    public function test_完整重置密码流程(): void
    {
        // 1. 请求重置链接
        $sendResponse = $this->postJson('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $sendResponse->assertOk();
        $token = $sendResponse->json('reset_token');
        $this->assertNotNull($token);

        // 2. 重置密码
        $resetResponse = $this->postJson('/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $resetResponse->assertOk()
            ->assertJsonPath('message', '密码重置成功');

        // 3. 验证旧密码不再可用
        $loginResponse = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'oldpassword',
        ]);
        $loginResponse->assertStatus(401);

        // 4. 新密码可用
        $newLoginResponse = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
        ]);
        $newLoginResponse->assertOk();
    }

    public function test_密码需确认一致(): void
    {
        $response = $this->postJson('/reset-password', [
            'email' => 'test@example.com',
            'token' => 'some-token',
            'password' => 'newpass123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }
}
