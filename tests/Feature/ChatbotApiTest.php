<?php

namespace Tests\Feature;

use App\Models\ChatMagic;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Cookie\Middleware\EncryptCookies::class);

        $this->prepareSchema();
        $this->truncateTables();

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash',
            'services.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'services.gemini.timeout' => 5,
            'services.gemini.force_json_response' => true,
        ]);
    }

    public function test_guest_can_load_empty_history_and_get_cookie(): void
    {
        $response = $this->getJson('/api/chat/history');

        $response->assertOk();
        $response->assertJson([
            'messages' => [],
        ]);
        $response->assertCookie('guest_token');
    }

    public function test_send_message_saves_user_and_assistant_records(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'assistant_text' => 'Ban nen kiem tra ong thoat nuoc va ve sinh dan lanh.',
                                        'cases' => [],
                                        'technicians' => [],
                                        'youtube_links' => [],
                                    ], JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/api/chat/send', [
            'text' => 'may lanh bi chay nuoc',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'assistant_text',
                'cases',
                'technicians',
                'youtube_links',
                'assistant_message_id',
            ],
        ]);

        $this->assertSame(2, ChatMagic::query()->count());
        $this->assertSame('user', ChatMagic::query()->orderBy('id')->first()->sender);
        $this->assertSame('assistant', ChatMagic::query()->orderByDesc('id')->first()->sender);

        Http::assertSent(function (HttpRequest $request): bool {
            $systemInstruction = (string) data_get($request->data(), 'systemInstruction.parts.0.text', '');

            return str_contains($systemInstruction, 'ASSISTANT SOUL')
                && str_contains($systemInstruction, 'ngat cau dao')
                && str_contains($systemInstruction, 'Can tho chuyen nghiep');
        });
    }

    public function test_sync_guest_moves_messages_to_logged_in_user(): void
    {
        $guestToken = 'sync-guest-token';
        ChatMagic::query()->create([
            'guest_token' => $guestToken,
            'sender' => 'user',
            'text' => 'tu lanh khong lanh',
            'created_at' => now(),
        ]);
        ChatMagic::query()->create([
            'guest_token' => $guestToken,
            'sender' => 'assistant',
            'text' => 'Ban kiem tra gioang cua va dan lanh.',
            'created_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'sync@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $syncResponse = $this->withHeader('X-Guest-Token', $guestToken)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/chat/sync-guest', []);

        $syncResponse->assertOk();
        $syncResponse->assertJson([
            'message' => 'Synced',
            'synced_count' => 2,
        ]);

        $this->assertSame(2, ChatMagic::query()->where('user_id', $user->id)->count());
    }

    public function test_history_returns_max_10_messages(): void
    {
        $guestToken = 'guest-test-token';
        for ($i = 1; $i <= 12; $i++) {
            ChatMagic::query()->create([
                'guest_token' => $guestToken,
                'sender' => $i % 2 === 0 ? 'assistant' : 'user',
                'text' => 'message ' . $i,
                'created_at' => now()->addSeconds($i),
            ]);
        }

        $response = $this->withHeader('X-Guest-Token', $guestToken)->getJson('/api/chat/history');

        $response->assertOk();
        $messages = $response->json('messages');
        $this->assertCount(10, $messages);
        $this->assertSame('message 3', $messages[0]['text']);
        $this->assertSame('message 12', $messages[9]['text']);
    }

    public function test_emergency_keywords_return_immediate_safety_response_without_calling_ai(): void
    {
        Http::fake();

        $response = $this->postJson('/api/chat/send', [
            'text' => 'o cam boc khoi va mui khet',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.model', null);

        $assistantText = (string) $response->json('data.assistant_text');
        $this->assertStringContainsString('giu khoang cach an toan', $assistantText);
        $this->assertStringContainsString('Can tho chuyen nghiep', $assistantText);

        Http::assertNothingSent();
    }

    public function test_service_search_returns_only_matching_technicians_without_ai_reply_fluff(): void
    {
        Http::fake();

        $airconServiceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may lanh',
        ]);
        $washerServiceId = DB::table('danh_muc_dich_vu')->insertGetId([
            'ten_dich_vu' => 'Sua may giat',
        ]);

        $airconWorkerId = DB::table('users')->insertGetId([
            'name' => 'Tho May Lanh',
            'email' => 'aircon@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $washerWorkerId = DB::table('users')->insertGetId([
            'name' => 'Tho May Giat',
            'email' => 'washer@example.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'is_active' => true,
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ho_so_tho')->insert([
            [
                'user_id' => $airconWorkerId,
                'kinh_nghiem' => 'Chuyen sua may lanh dan dung',
                'bang_gia_tham_khao' => '150000 - 300000',
                'trang_thai_duyet' => 'da_duyet',
                'dang_hoat_dong' => true,
                'danh_gia_trung_binh' => 4.9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $washerWorkerId,
                'kinh_nghiem' => 'Chuyen sua may giat cua ngang',
                'bang_gia_tham_khao' => '200000 - 350000',
                'trang_thai_duyet' => 'da_duyet',
                'dang_hoat_dong' => true,
                'danh_gia_trung_binh' => 4.8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tho_dich_vu')->insert([
            [
                'user_id' => $airconWorkerId,
                'dich_vu_id' => $airconServiceId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $washerWorkerId,
                'dich_vu_id' => $washerServiceId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('don_dat_lich')->insert([
            [
                'tho_id' => $airconWorkerId,
                'dich_vu_id' => $airconServiceId,
                'mo_ta_van_de' => 'May lanh khong mat',
                'giai_phap' => 'Nap gas va ve sinh',
                'trang_thai' => 'da_xong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tho_id' => $washerWorkerId,
                'dich_vu_id' => $washerServiceId,
                'mo_ta_van_de' => 'May giat khong vat',
                'giai_phap' => 'Thay day curoa',
                'trang_thai' => 'da_xong',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->postJson('/api/chat/send', [
            'text' => 'tim tho sua may lanh',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.model', null);
        $response->assertJsonCount(0, 'data.cases');
        $response->assertJsonCount(0, 'data.youtube_links');
        $response->assertJsonCount(1, 'data.technicians');
        $response->assertJsonPath('data.technicians.0.name', 'Tho May Lanh');

        $assistantText = (string) $response->json('data.assistant_text');
        $this->assertStringContainsString('tho sua may lanh', strtolower($assistantText));

        Http::assertNothingSent();
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar')->nullable();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chat_magic')) {
            Schema::create('chat_magic', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('guest_token')->nullable();
                $table->string('sender');
                $table->longText('text');
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('danh_muc_dich_vu')) {
            Schema::create('danh_muc_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->string('ten_dich_vu');
            });
        }

        if (!Schema::hasTable('don_dat_lich')) {
            Schema::create('don_dat_lich', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tho_id')->nullable();
                $table->unsignedBigInteger('dich_vu_id')->nullable();
                $table->text('mo_ta_van_de')->nullable();
                $table->text('giai_phap')->nullable();
                $table->json('hinh_anh_mo_ta')->nullable();
                $table->json('hinh_anh_ket_qua')->nullable();
                $table->string('trang_thai')->default('da_xong');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_knowledge_items')) {
            Schema::create('ai_knowledge_items', function (Blueprint $table) {
                $table->id();
                $table->string('source_type', 50);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_key')->unique();
                $table->unsignedBigInteger('primary_service_id')->nullable();
                $table->string('service_name')->nullable();
                $table->string('title');
                $table->longText('content');
                $table->longText('normalized_content')->nullable();
                $table->text('symptom_text')->nullable();
                $table->text('cause_text')->nullable();
                $table->text('solution_text')->nullable();
                $table->text('price_context')->nullable();
                $table->decimal('rating_avg', 3, 2)->nullable();
                $table->decimal('quality_score', 5, 4)->default(0);
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('danh_gia')) {
            Schema::create('danh_gia', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->integer('so_sao')->default(5);
            });
        }

        if (!Schema::hasTable('ho_so_tho')) {
            Schema::create('ho_so_tho', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->text('kinh_nghiem')->nullable();
                $table->string('bang_gia_tham_khao')->nullable();
                $table->string('trang_thai_duyet')->default('da_duyet');
                $table->boolean('dang_hoat_dong')->default(true);
                $table->decimal('danh_gia_trung_binh', 3, 2)->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasColumn('ho_so_tho', 'bang_gia_tham_khao')) {
            Schema::table('ho_so_tho', function (Blueprint $table) {
                $table->string('bang_gia_tham_khao')->nullable();
            });
        }

        if (!Schema::hasTable('tho_dich_vu')) {
            Schema::create('tho_dich_vu', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('dich_vu_id');
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach ([
            'chat_magic',
            'ai_knowledge_items',
            'danh_gia',
            'don_dat_lich',
            'tho_dich_vu',
            'ho_so_tho',
            'danh_muc_dich_vu',
            'personal_access_tokens',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
