<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonDatLich;
use App\Models\ThanhToan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPaymentUrl(Request $request)
    {
        $validated = $request->validate([
            'don_dat_lich_id' => 'required|exists:don_dat_lich,id',
            'phuong_thuc' => 'required|in:vnpay,momo,zalopay',
        ]);

        $booking = DonDatLich::findOrFail($validated['don_dat_lich_id']);
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Ban can dang nhap de thanh toan.',
            ], 401);
        }

        if ((int) $booking->khach_hang_id !== (int) $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen thanh toan don nay.',
            ], 403);
        }

        if (!in_array($booking->trang_thai, ['cho_thanh_toan', 'cho_hoan_thanh'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Don nay chua san sang de thanh toan.',
            ], 400);
        }

        $totalAmount = $this->resolvePaymentAmount($booking);
        if ($totalAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'So tien thanh toan khong hop le. Vui long cap nhat chi phi truoc khi giao dich.',
            ], 422);
        }

        return match ($validated['phuong_thuc']) {
            'vnpay' => $this->createVnpayPayment($request, $booking, $totalAmount),
            'momo' => $this->createMomoPayment($request, $booking, $totalAmount),
            'zalopay' => $this->createZalopayPayment($request, $booking, $totalAmount),
        };
    }

    public function vnpayReturn(Request $request)
    {
        $inputData = $this->extractPrefixedFields($request, 'vnp_');
        $receivedHash = (string) $request->input('vnp_SecureHash', '');

        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $secret = (string) env('VNP_HASH_SECRET', '');
        $calculatedHash = hash_hmac('sha512', http_build_query($inputData, '', '&', PHP_QUERY_RFC3986), $secret);

        $txnRef = (string) $request->input('vnp_TxnRef', '');
        $bookingId = (int) explode('_', $txnRef)[0];

        if (!hash_equals($calculatedHash, $receivedHash)) {
            return redirect('/customer/my-bookings?payment=invalid_signature');
        }

        if ($request->input('vnp_ResponseCode') !== '00') {
            return redirect('/customer/my-bookings?payment=failed');
        }

        $this->processSuccessPayment(
            $bookingId,
            ((float) $request->input('vnp_Amount', 0)) / 100,
            'vnpay',
            $txnRef,
            $request->all()
        );

        return redirect('/customer/my-bookings?payment=success');
    }

    public function vnpayIpn(Request $request)
    {
        $inputData = $this->extractPrefixedFields($request, 'vnp_');
        $receivedHash = (string) $request->input('vnp_SecureHash', '');

        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $secret = (string) env('VNP_HASH_SECRET', '');
        $calculatedHash = hash_hmac('sha512', http_build_query($inputData, '', '&', PHP_QUERY_RFC3986), $secret);

        if (!hash_equals($calculatedHash, $receivedHash)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $txnRef = (string) $request->input('vnp_TxnRef', '');
        $bookingId = (int) explode('_', $txnRef)[0];
        $booking = DonDatLich::find($bookingId);

        if (!$booking) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if ($request->input('vnp_ResponseCode') === '00') {
            $this->processSuccessPayment(
                $bookingId,
                ((float) $request->input('vnp_Amount', 0)) / 100,
                'vnpay',
                $txnRef,
                $request->all()
            );
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    public function momoReturn(Request $request)
    {
        $signature = $this->buildMomoReturnSignature($request);
        $receivedSignature = (string) $request->input('signature', '');
        $orderId = (string) $request->input('orderId', '');
        $bookingId = (int) explode('_', $orderId)[0];

        if (!hash_equals($signature, $receivedSignature)) {
            return redirect('/customer/my-bookings?payment=invalid_signature');
        }

        if ((string) $request->input('resultCode') !== '0') {
            return redirect('/customer/my-bookings?payment=failed');
        }

        $this->processSuccessPayment(
            $bookingId,
            (float) $request->input('amount', 0),
            'momo',
            (string) $request->input('transId', ''),
            $request->all()
        );

        return redirect('/customer/my-bookings?payment=success');
    }

    public function momoIpn(Request $request)
    {
        $signature = $this->buildMomoReturnSignature($request);
        $receivedSignature = (string) $request->input('signature', '');

        if (!hash_equals($signature, $receivedSignature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $orderId = (string) $request->input('orderId', '');
        $bookingId = (int) explode('_', $orderId)[0];
        $booking = DonDatLich::find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ((string) $request->input('resultCode') === '0') {
            $this->processSuccessPayment(
                $bookingId,
                (float) $request->input('amount', 0),
                'momo',
                (string) $request->input('transId', ''),
                $request->all()
            );
        }

        return response()->json([], 204);
    }

    public function zalopayReturn(Request $request)
    {
        return redirect('/customer/my-bookings?payment=' . ($request->input('status') == 1 ? 'success' : 'failed'));
    }

    public function zalopayIpn(Request $request)
    {
        $key2 = (string) env('ZALOPAY_KEY2', '');
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || empty($payload['data']) || empty($payload['mac'])) {
            return response()->json(['return_code' => -1, 'return_message' => 'invalid payload']);
        }

        $calculatedMac = hash_hmac('sha256', $payload['data'], $key2);
        if (!hash_equals($calculatedMac, (string) $payload['mac'])) {
            return response()->json(['return_code' => -1, 'return_message' => 'mac not equal']);
        }

        $data = json_decode($payload['data'], true);
        $parts = explode('_', (string) ($data['app_trans_id'] ?? ''));
        $bookingId = isset($parts[1]) ? (int) $parts[1] : 0;

        if ($bookingId > 0) {
            $this->processSuccessPayment(
                $bookingId,
                (float) ($data['amount'] ?? 0),
                'zalopay',
                (string) ($data['zp_trans_id'] ?? ''),
                $payload['data']
            );
        }

        return response()->json(['return_code' => 1, 'return_message' => 'success']);
    }

    private function createVnpayPayment(Request $request, DonDatLich $booking, float $totalAmount)
    {
        $tmnCode = (string) env('VNP_TMN_CODE', '');
        $hashSecret = (string) env('VNP_HASH_SECRET', '');
        $gatewayUrl = (string) env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');

        if ($tmnCode === '' || $hashSecret === '') {
            return response()->json([
                'success' => false,
                'message' => 'VNPay chua duoc cau hinh trong file .env.',
            ], 500);
        }

        $txnRef = $booking->id . '_' . time();
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) round($totalAmount * 100),
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don dat lich #' . $booking->id,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $this->buildCallbackUrl($request, 'vnpay-return'),
            'vnp_TxnRef' => $txnRef,
        ];

        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $secureHash = hash_hmac('sha512', $query, $hashSecret);
        $url = $gatewayUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    private function createMomoPayment(Request $request, DonDatLich $booking, float $totalAmount)
    {
        $partnerCode = (string) env('MOMO_PARTNER_CODE', '');
        $accessKey = (string) env('MOMO_ACCESS_KEY', '');
        $secretKey = (string) env('MOMO_SECRET_KEY', '');
        $endpoint = (string) env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');

        if ($partnerCode === '' || $accessKey === '' || $secretKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'MoMo chua duoc cau hinh trong file .env.',
            ], 500);
        }

        $orderId = $booking->id . '_' . time();
        $requestId = (string) time();
        $redirectUrl = $this->buildCallbackUrl($request, 'momo-return');
        $ipnUrl = $this->buildCallbackUrl($request, 'momo-ipn');
        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'ThoTotNTU',
            'storeId' => 'ThoTotNTUStore',
            'requestId' => $requestId,
            'amount' => (string) round($totalAmount),
            'orderId' => $orderId,
            'orderInfo' => 'Thanh toan don dat lich #' . $booking->id,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => 'captureWallet',
        ];

        $rawHash = 'accessKey=' . $accessKey
            . '&amount=' . $payload['amount']
            . '&extraData=' . $payload['extraData']
            . '&ipnUrl=' . $payload['ipnUrl']
            . '&orderId=' . $payload['orderId']
            . '&orderInfo=' . $payload['orderInfo']
            . '&partnerCode=' . $payload['partnerCode']
            . '&redirectUrl=' . $payload['redirectUrl']
            . '&requestId=' . $payload['requestId']
            . '&requestType=' . $payload['requestType'];

        $payload['signature'] = hash_hmac('sha256', $rawHash, $secretKey);

        try {
            $response = Http::acceptJson()->post($endpoint, $payload);
            $json = $response->json();

            if ($response->successful() && !empty($json['payUrl'])) {
                return response()->json([
                    'success' => true,
                    'url' => $json['payUrl'],
                ]);
            }

            Log::error('MoMo create payment failed', [
                'status' => $response->status(),
                'response' => $json,
            ]);

            return response()->json([
                'success' => false,
                'message' => $json['message'] ?? 'Khong tao duoc giao dich MoMo.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('MoMo request error', ['exception' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Khong ket noi duoc cong thanh toan MoMo.',
            ], 500);
        }
    }

    private function createZalopayPayment(Request $request, DonDatLich $booking, float $totalAmount)
    {
        $appId = (string) env('ZALOPAY_APP_ID', '');
        $key1 = (string) env('ZALOPAY_KEY1', '');
        $endpoint = (string) env('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create');

        if ($appId === '' || $key1 === '') {
            return response()->json([
                'success' => false,
                'message' => 'ZaloPay chua duoc cau hinh trong file .env.',
            ], 500);
        }

        $embedData = json_encode(['redirecturl' => $this->buildCallbackUrl($request, 'zalopay-return')], JSON_UNESCAPED_SLASHES);
        $item = json_encode([['don_dat_lich' => $booking->id]], JSON_UNESCAPED_SLASHES);
        $appTransId = date('ymd') . '_' . $booking->id . '_' . time();

        $order = [
            'app_id' => $appId,
            'app_time' => round(microtime(true) * 1000),
            'app_trans_id' => $appTransId,
            'app_user' => 'user_' . ($booking->khach_hang_id ?? 'guest'),
            'item' => $item,
            'embed_data' => $embedData,
            'amount' => (int) round($totalAmount),
            'description' => 'Thanh toan don dat lich #' . $booking->id,
            'bank_code' => '',
        ];

        $data = $order['app_id']
            . '|' . $order['app_trans_id']
            . '|' . $order['app_user']
            . '|' . $order['amount']
            . '|' . $order['app_time']
            . '|' . $order['embed_data']
            . '|' . $order['item'];

        $order['mac'] = hash_hmac('sha256', $data, $key1);

        try {
            $response = Http::asForm()->post($endpoint, $order);
            $json = $response->json();

            if ($response->successful() && !empty($json['order_url'])) {
                return response()->json([
                    'success' => true,
                    'url' => $json['order_url'],
                ]);
            }

            Log::error('ZaloPay create payment failed', [
                'status' => $response->status(),
                'response' => $json,
            ]);

            return response()->json([
                'success' => false,
                'message' => $json['return_message'] ?? 'Khong tao duoc giao dich ZaloPay.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('ZaloPay request error', ['exception' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Khong ket noi duoc cong thanh toan ZaloPay.',
            ], 500);
        }
    }

    private function processSuccessPayment(int $bookingId, float $amount, string $method, string $transactionId, array|string $extraInfo): void
    {
        if ($bookingId <= 0) {
            return;
        }

        $existing = ThanhToan::query()
            ->where('don_dat_lich_id', $bookingId)
            ->where('trang_thai', 'success')
            ->first();

        if ($existing) {
            return;
        }

        ThanhToan::create([
            'don_dat_lich_id' => $bookingId,
            'so_tien' => $amount,
            'phuong_thuc' => $method,
            'ma_giao_dich' => $transactionId,
            'trang_thai' => 'success',
            'thong_tin_extra' => is_array($extraInfo) ? $extraInfo : ['raw' => $extraInfo],
        ]);

        $booking = DonDatLich::find($bookingId);
        if (!$booking) {
            return;
        }

        if (in_array($booking->trang_thai, ['cho_thanh_toan', 'cho_hoan_thanh'], true)) {
            $booking->trang_thai = 'da_xong';
        }

        $booking->trang_thai_thanh_toan = true;
        $booking->save();
    }

    private function resolvePaymentAmount(DonDatLich $booking): float
    {
        $tongTien = (float) ($booking->tong_tien ?? 0);
        if ($tongTien > 0) {
            return $tongTien;
        }

        return (float) ($booking->phi_di_lai ?? 0)
            + (float) ($booking->phi_linh_kien ?? 0)
            + (float) ($booking->tien_cong ?? 0)
            + (float) ($booking->tien_thue_xe ?? 0);
    }

    private function buildCallbackUrl(Request $request, string $suffix): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/') . '/api/payment/' . ltrim($suffix, '/');
    }

    private function extractPrefixedFields(Request $request, string $prefix): array
    {
        $fields = [];

        foreach ($request->all() as $key => $value) {
            if (str_starts_with((string) $key, $prefix)) {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    private function buildMomoReturnSignature(Request $request): string
    {
        $accessKey = (string) env('MOMO_ACCESS_KEY', '');
        $secretKey = (string) env('MOMO_SECRET_KEY', '');

        $rawHash = 'accessKey=' . $accessKey
            . '&amount=' . $request->input('amount', '')
            . '&extraData=' . $request->input('extraData', '')
            . '&message=' . $request->input('message', '')
            . '&orderId=' . $request->input('orderId', '')
            . '&orderInfo=' . $request->input('orderInfo', '')
            . '&orderType=' . $request->input('orderType', '')
            . '&partnerCode=' . $request->input('partnerCode', '')
            . '&payType=' . $request->input('payType', '')
            . '&requestId=' . $request->input('requestId', '')
            . '&responseTime=' . $request->input('responseTime', '')
            . '&resultCode=' . $request->input('resultCode', '')
            . '&transId=' . $request->input('transId', '');

        return hash_hmac('sha256', $rawHash, $secretKey);
    }
}
