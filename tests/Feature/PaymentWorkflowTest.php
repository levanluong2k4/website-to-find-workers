<?php

namespace Tests\Feature;

use App\Services\Chat\AiKnowledgeSyncService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['phone_verification.required' => false]);
        Notification::fake();

        $this->app->instance(AiKnowledgeSyncService::class, new class {
            public function syncBookingCases(?int $bookingId = null): int
            {
                return 0;
            }
        });

        $this->prepareSchema();
        $this->truncateTables();
    }

    public function test_customer_cannot_mark_cash_booking_complete_directly(): void
    {
        $customer = $this->createUser('customer', 'cash-customer@example.com');
        $worker = $this->createUser('worker', 'cash-worker@example.com');
        $token = $customer->createToken('cash-customer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_hoan_thanh',
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 550000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/status", [
                'trang_thai' => 'da_xong',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Khach hang khong the tu xac nhan hoan tat. Hay thanh toan chuyen khoan tren he thong hoac doi tho xac nhan tien mat.');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'cho_hoan_thanh',
            'trang_thai_thanh_toan' => 0,
        ]);
    }

    public function test_worker_confirming_cash_payment_marks_booking_complete(): void
    {
        $customer = $this->createUser('customer', 'confirm-cash-customer@example.com');
        $worker = $this->createUser('worker', 'confirm-cash-worker@example.com');
        $token = $worker->createToken('cash-worker')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_hoan_thanh',
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 780000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/bookings/{$bookingId}/confirm-cash-payment");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.trang_thai', 'da_xong');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'cash',
            'trang_thai' => 'success',
        ]);
    }

    public function test_customer_can_switch_updated_booking_to_transfer_before_worker_finishes(): void
    {
        $customer = $this->createUser('customer', 'switch-transfer-customer@example.com');
        $worker = $this->createUser('worker', 'switch-transfer-worker@example.com');
        $token = $customer->createToken('switch-transfer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'dang_lam',
            'gia_da_cap_nhat' => true,
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 930000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/bookings/{$bookingId}/payment-method", [
                'phuong_thuc_thanh_toan' => 'transfer',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.trang_thai', 'dang_lam')
            ->assertJsonPath('booking.phuong_thuc_thanh_toan', 'transfer');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'dang_lam',
            'phuong_thuc_thanh_toan' => 'transfer',
        ]);
    }

    public function test_customer_can_switch_pending_cod_booking_to_online_payment(): void
    {
        $customer = $this->createUser('customer', 'switch-pending-transfer@example.com');
        $worker = $this->createUser('worker', 'switch-pending-transfer-worker@example.com');
        $token = $customer->createToken('switch-pending-transfer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_hoan_thanh',
            'gia_da_cap_nhat' => true,
            'phuong_thuc_thanh_toan' => 'cod',
            'tong_tien' => 640000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/bookings/{$bookingId}/payment-method", [
                'phuong_thuc_thanh_toan' => 'transfer',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.trang_thai', 'cho_thanh_toan')
            ->assertJsonPath('booking.phuong_thuc_thanh_toan', 'transfer');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
        ]);
    }

    public function test_customer_test_payment_completes_transfer_booking(): void
    {
        $customer = $this->createUser('customer', 'transfer-customer@example.com');
        $worker = $this->createUser('worker', 'transfer-worker@example.com');
        $token = $customer->createToken('transfer-customer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 1250000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/create', [
                'don_dat_lich_id' => $bookingId,
                'phuong_thuc' => 'test',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_status', 'success');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'test',
            'trang_thai' => 'success',
        ]);
    }

    public function test_customer_can_create_momo_payment_url_when_gateway_is_configured(): void
    {
        $this->setMomoEnvironment();
        Http::fake([
            'https://test-payment.momo.vn/*' => Http::response([
                'resultCode' => 0,
                'message' => 'Successful.',
                'payUrl' => 'https://test-payment.momo.vn/mock-pay-url',
            ], 200),
        ]);

        $customer = $this->createUser('customer', 'momo-customer@example.com');
        $worker = $this->createUser('worker', 'momo-worker@example.com');
        $token = $customer->createToken('momo-customer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 80868,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/create', [
                'don_dat_lich_id' => $bookingId,
                'phuong_thuc' => 'momo',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('url', 'https://test-payment.momo.vn/mock-pay-url');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($bookingId): bool {
            $payload = $request->data();

            return $request->url() === 'https://test-payment.momo.vn/v2/gateway/api/create'
                && ($payload['partnerCode'] ?? null) === 'MOMOBKUN20180529'
                && ($payload['requestType'] ?? null) === 'captureWallet'
                && (string) ($payload['amount'] ?? '') === '80868'
                && str_starts_with((string) ($payload['orderId'] ?? ''), $bookingId . '_')
                && str_ends_with((string) ($payload['redirectUrl'] ?? ''), '/api/payment/momo-return')
                && str_ends_with((string) ($payload['ipnUrl'] ?? ''), '/api/payment/momo-ipn');
        });
    }

    public function test_customer_can_create_vnpay_payment_url_when_gateway_is_configured(): void
    {
        $this->setVnpayEnvironment();
        Carbon::setTestNow(Carbon::parse('2026-04-22 08:25:30', 'UTC'));

        try {
            $customer = $this->createUser('customer', 'vnpay-customer@example.com');
            $worker = $this->createUser('worker', 'vnpay-worker@example.com');
            $token = $customer->createToken('vnpay-customer')->plainTextToken;

            $bookingId = $this->createBooking([
                'khach_hang_id' => $customer->id,
                'tho_id' => $worker->id,
                'trang_thai' => 'cho_thanh_toan',
                'phuong_thuc_thanh_toan' => 'transfer',
                'tong_tien' => 80868,
            ]);

            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/payment/create', [
                    'don_dat_lich_id' => $bookingId,
                    'phuong_thuc' => 'vnpay',
                ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $url = (string) $response->json('url');
            $this->assertNotSame('', $url);

            $parts = parse_url($url);
            $rawQuery = (string) ($parts['query'] ?? '');
            parse_str($rawQuery, $query);

            $this->assertSame('https', $parts['scheme'] ?? null);
            $this->assertSame('sandbox.vnpayment.vn', $parts['host'] ?? null);
            $this->assertSame('/paymentv2/vpcpay.html', $parts['path'] ?? null);
            $this->assertStringContainsString('vnp_OrderInfo=Thanh+toan+don+dat+lich+ma+' . $bookingId, $rawQuery);
            $this->assertSame('D0L2KPV8', $query['vnp_TmnCode'] ?? null);
            $this->assertSame('8086800', (string) ($query['vnp_Amount'] ?? ''));
            $this->assertSame('NCB', $query['vnp_BankCode'] ?? null);
            $this->assertSame('20260422152530', (string) ($query['vnp_CreateDate'] ?? ''));
            $this->assertSame('20260422154030', (string) ($query['vnp_ExpireDate'] ?? ''));
            $this->assertSame('vn', $query['vnp_Locale'] ?? null);
            $this->assertSame('Thanh toan don dat lich ma ' . $bookingId, $query['vnp_OrderInfo'] ?? null);
            $this->assertSame('other', $query['vnp_OrderType'] ?? null);
            $this->assertSame('http://localhost/api/payment/vnpay-return', $query['vnp_ReturnUrl'] ?? null);
            $this->assertStringStartsWith($bookingId . '_', (string) ($query['vnp_TxnRef'] ?? ''));

            $receivedSecureHash = (string) ($query['vnp_SecureHash'] ?? '');
            $this->assertSame($this->signVnpayPayload($query), $receivedSecureHash);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_vnpay_return_marks_transfer_booking_paid_when_signature_is_valid(): void
    {
        $this->setVnpayEnvironment();

        $customer = $this->createUser('customer', 'vnpay-return-customer@example.com');
        $worker = $this->createUser('worker', 'vnpay-return-worker@example.com');

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 80868,
        ]);

        $query = [
            'vnp_Amount' => '8086800',
            'vnp_BankCode' => 'NCB',
            'vnp_BankTranNo' => 'VNP14869839',
            'vnp_CardType' => 'ATM',
            'vnp_OrderInfo' => 'Thanh toan don dat lich ma ' . $bookingId,
            'vnp_PayDate' => '20260422153021',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'D0L2KPV8',
            'vnp_TransactionNo' => '14869839',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $bookingId . '_1713778050',
        ];
        $query['vnp_SecureHash'] = $this->signVnpayPayload($query);

        $response = $this->get('/api/payment/vnpay-return?' . http_build_query($query));

        $response->assertRedirect('/customer/my-bookings?payment=success');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'vnpay',
            'ma_giao_dich' => $query['vnp_TxnRef'],
            'trang_thai' => 'success',
        ]);
    }

    public function test_customer_can_create_zalopay_payment_url_when_gateway_is_configured(): void
    {
        $this->setZalopayEnvironment();
        Carbon::setTestNow(Carbon::parse('2026-04-22 08:43:06', 'UTC'));

        try {
            Http::fake([
                'https://sb-openapi.zalopay.vn/v2/create' => Http::response([
                    'return_code' => 1,
                    'return_message' => 'Giao dịch thành công',
                    'sub_return_code' => 1,
                    'sub_return_message' => 'Giao dịch thành công',
                    'order_url' => 'https://sandbox.zalopay.vn/pay/mock-order-url',
                ], 200),
            ]);

            $customer = $this->createUser('customer', 'zalopay-customer@example.com');
            $worker = $this->createUser('worker', 'zalopay-worker@example.com');
            $token = $customer->createToken('zalopay-customer')->plainTextToken;

            $bookingId = $this->createBooking([
                'khach_hang_id' => $customer->id,
                'tho_id' => $worker->id,
                'trang_thai' => 'cho_thanh_toan',
                'phuong_thuc_thanh_toan' => 'transfer',
                'tong_tien' => 850000,
            ]);

            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/payment/create', [
                    'don_dat_lich_id' => $bookingId,
                    'phuong_thuc' => 'zalopay',
                ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('url', 'https://sandbox.zalopay.vn/pay/mock-order-url');

            Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($bookingId): bool {
                $payload = $request->data();
                $embedData = json_decode((string) ($payload['embed_data'] ?? '{}'), true);
                $items = json_decode((string) ($payload['item'] ?? '[]'), true);

                return $request->url() === 'https://sb-openapi.zalopay.vn/v2/create'
                    && ($payload['app_id'] ?? null) === '2554'
                    && ($payload['app_time'] ?? null) === '1776847386000'
                    && ($payload['app_trans_id'] ?? null) === '260422_' . $bookingId . '_1776847386'
                    && ($payload['callback_url'] ?? null) === 'http://localhost/api/payment/zalopay-ipn'
                    && ($payload['expire_duration_seconds'] ?? null) === '900'
                    && ($payload['bank_code'] ?? null) === ''
                    && ($payload['amount'] ?? null) === '850000'
                    && ($payload['description'] ?? null) === 'Thanh toan don dat lich ma ' . $bookingId
                    && ($embedData['redirecturl'] ?? null) === 'http://localhost/api/payment/zalopay-return'
                    && ($embedData['preferred_payment_method'][0] ?? null) === 'zalopay_wallet'
                    && (int) (($items[0]['booking_id'] ?? 0)) === $bookingId
                    && ($payload['mac'] ?? null) === $this->signZalopayCreatePayload($payload);
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_zalopay_return_queries_status_and_marks_booking_paid_when_checksum_is_valid(): void
    {
        $this->setZalopayEnvironment();
        Http::fake([
            'https://sb-openapi.zalopay.vn/v2/query' => Http::response([
                'return_code' => 1,
                'return_message' => 'SUCCESS',
                'sub_return_code' => 1,
                'sub_return_message' => 'SUCCESS',
                'amount' => 850000,
                'zp_trans_id' => 987654321,
                'server_time' => 1776847440000,
            ], 200),
        ]);

        $customer = $this->createUser('customer', 'zalopay-return-customer@example.com');
        $worker = $this->createUser('worker', 'zalopay-return-worker@example.com');

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 850000,
        ]);

        $query = [
            'appid' => '2554',
            'apptransid' => '260422_' . $bookingId . '_1776847386',
            'pmcid' => '38',
            'bankcode' => '',
            'amount' => '850000',
            'discountamount' => '0',
            'status' => '1',
        ];
        $query['checksum'] = $this->signZalopayRedirectPayload($query);

        $response = $this->get('/api/payment/zalopay-return?' . http_build_query($query));

        $response->assertRedirect('/customer/my-bookings?payment=success');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($query): bool {
            $payload = $request->data();

            return $request->url() === 'https://sb-openapi.zalopay.vn/v2/query'
                && ($payload['app_id'] ?? null) === '2554'
                && ($payload['app_trans_id'] ?? null) === $query['apptransid']
                && ($payload['mac'] ?? null) === $this->signZalopayQueryPayload($payload);
        });

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'zalopay',
            'ma_giao_dich' => '987654321',
            'trang_thai' => 'success',
        ]);
    }

    public function test_customer_can_create_momo_atm_payment_url_when_gateway_is_configured(): void
    {
        $this->setMomoEnvironment();
        Http::fake([
            'https://test-payment.momo.vn/*' => Http::response([
                'resultCode' => 0,
                'message' => 'Successful.',
                'payUrl' => 'https://test-payment.momo.vn/mock-atm-pay-url',
            ], 200),
        ]);

        $customer = $this->createUser('customer', 'momo-atm-customer@example.com');
        $worker = $this->createUser('worker', 'momo-atm-worker@example.com');
        $token = $customer->createToken('momo-atm-customer')->plainTextToken;

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 30000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/create', [
                'don_dat_lich_id' => $bookingId,
                'phuong_thuc' => 'momo_atm',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('url', 'https://test-payment.momo.vn/mock-atm-pay-url');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($bookingId): bool {
            $payload = $request->data();

            return $request->url() === 'https://test-payment.momo.vn/v2/gateway/api/create'
                && ($payload['partnerCode'] ?? null) === 'MOMOBKUN20180529'
                && ($payload['requestType'] ?? null) === 'payWithATM'
                && (string) ($payload['amount'] ?? '') === '30000'
                && str_starts_with((string) ($payload['orderId'] ?? ''), $bookingId . '_')
                && str_contains((string) ($payload['orderInfo'] ?? ''), 'MoMo ATM');
        });
    }

    public function test_momo_return_marks_transfer_booking_paid_when_signature_is_valid(): void
    {
        $this->setMomoEnvironment();

        $customer = $this->createUser('customer', 'momo-return-customer@example.com');
        $worker = $this->createUser('worker', 'momo-return-worker@example.com');

        $bookingId = $this->createBooking([
            'khach_hang_id' => $customer->id,
            'tho_id' => $worker->id,
            'trang_thai' => 'cho_thanh_toan',
            'phuong_thuc_thanh_toan' => 'transfer',
            'tong_tien' => 80868,
        ]);

        $query = [
            'partnerCode' => 'MOMOBKUN20180529',
            'orderId' => $bookingId . '_1742968258',
            'requestId' => '1742968258',
            'amount' => '80868',
            'orderInfo' => 'Thanh toan don dat lich #' . $bookingId,
            'orderType' => 'momo_wallet',
            'transId' => '4375415923',
            'resultCode' => '0',
            'message' => 'Successful.',
            'payType' => 'webApp',
            'responseTime' => '1742968326921',
            'extraData' => '',
        ];
        $query['signature'] = $this->signMomoReturnPayload($query);

        $response = $this->get('/api/payment/momo-return?' . http_build_query($query));

        $response->assertRedirect('/customer/my-bookings?payment=success');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);

        $this->assertDatabaseHas('thanh_toan', [
            'don_dat_lich_id' => $bookingId,
            'phuong_thuc' => 'momo',
            'ma_giao_dich' => '4375415923',
            'trang_thai' => 'success',
        ]);
    }

    public function test_admin_cannot_change_status_of_completed_booking(): void
    {
        $admin = $this->createUser('admin', 'immutable-status-admin@example.com');
        $token = $admin->createToken('immutable-status-admin')->plainTextToken;

        $bookingId = $this->createBooking([
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/don-dat-lich/{$bookingId}/status", [
                'trang_thai' => 'dang_lam',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Don da hoan thanh, khong the chinh sua nua.');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => 1,
        ]);
    }

    public function test_admin_cannot_update_financials_of_completed_booking(): void
    {
        $admin = $this->createUser('admin', 'immutable-financial-admin@example.com');
        $token = $admin->createToken('immutable-financial-admin')->plainTextToken;

        $bookingId = $this->createBooking([
            'trang_thai' => 'da_xong',
            'trang_thai_thanh_toan' => true,
            'gia_da_cap_nhat' => true,
            'tien_cong' => 150000,
            'tong_tien' => 150000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/bookings/{$bookingId}/financials", [
                'tien_cong' => 280000,
                'phi_di_lai' => 10000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Don da hoan thanh, khong the chinh sua nua.');

        $this->assertDatabaseHas('don_dat_lich', [
            'id' => $bookingId,
            'trang_thai' => 'da_xong',
            'tien_cong' => 150000,
            'phi_di_lai' => 0,
            'tong_tien' => 150000,
        ]);
    }

    private function createUser(string $role, string $email): \App\Models\User
    {
        return \App\Models\User::query()->create([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createBooking(array $attributes = []): int
    {
        return DB::table('don_dat_lich')->insertGetId(array_merge([
            'khach_hang_id' => null,
            'tho_id' => null,
            'trang_thai' => 'cho_xac_nhan',
            'phuong_thuc_thanh_toan' => 'cod',
            'gia_da_cap_nhat' => false,
            'tong_tien' => 0,
            'phi_di_lai' => 0,
            'phi_linh_kien' => 0,
            'tien_cong' => 0,
            'tien_thue_xe' => 0,
            'trang_thai_thanh_toan' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function prepareSchema(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('role', ['admin', 'customer', 'worker'])->default('customer');
                $table->boolean('is_active')->default(true);
                $table->timestamp('phone_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
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

        if (!Schema::hasTable('don_dat_lich')) {
            Schema::create('don_dat_lich', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('khach_hang_id')->nullable();
                $table->unsignedBigInteger('tho_id')->nullable();
                $table->string('trang_thai')->default('cho_xac_nhan');
                $table->string('phuong_thuc_thanh_toan')->default('cod');
                $table->boolean('gia_da_cap_nhat')->default(false);
                $table->decimal('tong_tien', 12, 2)->default(0);
                $table->decimal('phi_di_lai', 12, 2)->default(0);
                $table->decimal('phi_linh_kien', 12, 2)->default(0);
                $table->decimal('tien_cong', 12, 2)->default(0);
                $table->decimal('tien_thue_xe', 12, 2)->default(0);
                $table->timestamp('thoi_gian_hoan_thanh')->nullable();
                $table->boolean('trang_thai_thanh_toan')->default(false);
                $table->timestamps();
            });
        }

        Schema::table('don_dat_lich', function (Blueprint $table) {
            if (!Schema::hasColumn('don_dat_lich', 'gia_da_cap_nhat')) {
                $table->boolean('gia_da_cap_nhat')->default(false)->after('phuong_thuc_thanh_toan');
            }
        });

        if (!Schema::hasTable('thanh_toan')) {
            Schema::create('thanh_toan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('don_dat_lich_id');
                $table->decimal('so_tien', 12, 2)->default(0);
                $table->string('phuong_thuc')->default('cash');
                $table->string('ma_giao_dich')->nullable();
                $table->string('trang_thai')->default('pending');
                $table->json('thong_tin_extra')->nullable();
                $table->timestamps();
            });
        }
    }

    private function truncateTables(): void
    {
        foreach (['thanh_toan', 'don_dat_lich', 'personal_access_tokens', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function setMomoEnvironment(): void
    {
        $this->setEnvironmentValue('APP_URL', 'http://localhost');
        $this->setEnvironmentValue('PAYMENT_CALLBACK_BASE_URL', 'http://localhost');
        $this->setEnvironmentValue('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
        $this->setEnvironmentValue('MOMO_PARTNER_CODE', 'MOMOBKUN20180529');
        $this->setEnvironmentValue('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j');
        $this->setEnvironmentValue('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');
    }

    private function setVnpayEnvironment(): void
    {
        $this->setEnvironmentValue('APP_URL', 'http://localhost');
        $this->setEnvironmentValue('PAYMENT_CALLBACK_BASE_URL', 'http://localhost');
        $this->setEnvironmentValue('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $this->setEnvironmentValue('VNP_TMN_CODE', 'D0L2KPV8');
        $this->setEnvironmentValue('VNP_HASH_SECRET', '7A4ALE4PESOGX9TKV2BD85CEUTS1H749');
        $this->setEnvironmentValue('VNP_BANK_CODE', 'NCB');
        $this->setEnvironmentValue('VNP_EXPIRE_MINUTES', '15');
        $this->setEnvironmentValue('VNP_TIMEZONE', 'Asia/Ho_Chi_Minh');
    }

    private function setZalopayEnvironment(): void
    {
        $this->setEnvironmentValue('APP_URL', 'http://localhost');
        $this->setEnvironmentValue('PAYMENT_CALLBACK_BASE_URL', 'http://localhost');
        $this->setEnvironmentValue('ZALOPAY_APP_ID', '2554');
        $this->setEnvironmentValue('ZALOPAY_KEY1', 'sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn');
        $this->setEnvironmentValue('ZALOPAY_KEY2', 'trMrHtvjo6myautxDUiAcYsVtaeQ8nhf');
        $this->setEnvironmentValue('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create');
        $this->setEnvironmentValue('ZALOPAY_QUERY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/query');
        $this->setEnvironmentValue('ZALOPAY_TIMEZONE', 'Asia/Ho_Chi_Minh');
        $this->setEnvironmentValue('ZALOPAY_EXPIRE_DURATION_SECONDS', '900');
        $this->setEnvironmentValue('ZALOPAY_BANK_CODE', '');
        $this->setEnvironmentValue('ZALOPAY_PREFERRED_PAYMENT_METHODS', 'zalopay_wallet');
    }

    private function setEnvironmentValue(string $key, string $value): void
    {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * @param array<string, string> $payload
     */
    private function signMomoReturnPayload(array $payload): string
    {
        $accessKey = (string) ($_ENV['MOMO_ACCESS_KEY'] ?? '');
        $secretKey = (string) ($_ENV['MOMO_SECRET_KEY'] ?? '');

        $rawHash = 'accessKey=' . $accessKey
            . '&amount=' . ($payload['amount'] ?? '')
            . '&extraData=' . ($payload['extraData'] ?? '')
            . '&message=' . ($payload['message'] ?? '')
            . '&orderId=' . ($payload['orderId'] ?? '')
            . '&orderInfo=' . ($payload['orderInfo'] ?? '')
            . '&orderType=' . ($payload['orderType'] ?? '')
            . '&partnerCode=' . ($payload['partnerCode'] ?? '')
            . '&payType=' . ($payload['payType'] ?? '')
            . '&requestId=' . ($payload['requestId'] ?? '')
            . '&responseTime=' . ($payload['responseTime'] ?? '')
            . '&resultCode=' . ($payload['resultCode'] ?? '')
            . '&transId=' . ($payload['transId'] ?? '');

        return hash_hmac('sha256', $rawHash, $secretKey);
    }

    /**
     * @param array<string, string> $payload
     */
    private function signVnpayPayload(array $payload): string
    {
        $secret = (string) ($_ENV['VNP_HASH_SECRET'] ?? '');

        unset($payload['vnp_SecureHash'], $payload['vnp_SecureHashType']);
        ksort($payload);

        return hash_hmac('sha512', http_build_query($payload, '', '&', PHP_QUERY_RFC1738), $secret);
    }

    /**
     * @param array<string, string> $payload
     */
    private function signZalopayCreatePayload(array $payload): string
    {
        $key1 = (string) ($_ENV['ZALOPAY_KEY1'] ?? '');

        $data = ($payload['app_id'] ?? '')
            . '|' . ($payload['app_trans_id'] ?? '')
            . '|' . ($payload['app_user'] ?? '')
            . '|' . ($payload['amount'] ?? '')
            . '|' . ($payload['app_time'] ?? '')
            . '|' . ($payload['embed_data'] ?? '')
            . '|' . ($payload['item'] ?? '');

        return hash_hmac('sha256', $data, $key1);
    }

    /**
     * @param array<string, string> $payload
     */
    private function signZalopayRedirectPayload(array $payload): string
    {
        $key2 = (string) ($_ENV['ZALOPAY_KEY2'] ?? '');

        $data = ($payload['appid'] ?? '')
            . '|' . ($payload['apptransid'] ?? '')
            . '|' . ($payload['pmcid'] ?? '')
            . '|' . ($payload['bankcode'] ?? '')
            . '|' . ($payload['amount'] ?? '')
            . '|' . ($payload['discountamount'] ?? '')
            . '|' . ($payload['status'] ?? '');

        return hash_hmac('sha256', $data, $key2);
    }

    /**
     * @param array<string, string> $payload
     */
    private function signZalopayQueryPayload(array $payload): string
    {
        $key1 = (string) ($_ENV['ZALOPAY_KEY1'] ?? '');

        return hash_hmac('sha256', ($payload['app_id'] ?? '') . '|' . ($payload['app_trans_id'] ?? '') . '|' . $key1, $key1);
    }
}
