<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonDatLich;
use App\Models\ThanhToan;
use App\Support\HttpClientTlsConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    public function createPaymentUrl(Request $request)
    {
        $validated = $request->validate([
            'don_dat_lich_id' => 'required|exists:don_dat_lich,id',
            'phuong_thuc' => 'required|in:test,vnpay,momo,momo_atm,zalopay',
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

        if (($booking->phuong_thuc_thanh_toan ?? 'cod') !== 'transfer') {
            return response()->json([
                'success' => false,
                'message' => 'Don nay dang su dung tien mat. Vui long thanh toan truc tiep cho tho va doi tho xac nhan.',
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
            'momo' => $this->createMomoWalletPayment($request, $booking, $totalAmount),
            'momo_atm' => $this->createMomoAtmPayment($request, $booking, $totalAmount),
            'zalopay' => $this->createZalopayPayment($request, $booking, $totalAmount),
            default => $this->createTestPayment($booking, $totalAmount),
        };
    }

    public function createWalletDepositUrl(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'phuong_thuc' => 'required|in:test,vnpay,momo,zalopay',
        ]);

        $user = $request->user();
        if (!$user || $user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Chi tho moi co the nap tien vao vi.'], 403);
        }

        $totalAmount = (float) $validated['amount'];
        $txnRef = 'WALLET_' . $user->id . '_' . time();
        $orderInfo = 'Nap ' . number_format($totalAmount) . ' VND vao vi tho ' . $user->id;

        return match ($validated['phuong_thuc']) {
            'vnpay' => $this->buildGenericVnpayUrl($request, $txnRef, $totalAmount, $orderInfo),
            'momo' => $this->buildGenericMomoUrl($request, $txnRef, $totalAmount, $orderInfo, 'payWithATM'),
            'zalopay' => $this->buildGenericZalopayUrl($request, $txnRef, $totalAmount, $orderInfo, $user->id),
            default => $this->processTestWalletDeposit($user->id, $totalAmount, $txnRef),
        };
    }

    public function requestWithdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:50000',
        ]);

        $user = $request->user();
        if (!$user || $user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Chỉ thợ mới có thể rút tiền.'], 403);
        }

        $amount = (float) $validated['amount'];

        $vi = \App\Models\ViDienTu::firstOrCreate(
            ['ma_tho' => $user->id],
            ['so_du' => 0, 'trang_thai' => 'hoat_dong']
        );

        if ($vi->so_du - $amount < 500000) {
            return response()->json([
                'success' => false,
                'message' => 'Số dư không đủ. Bạn phải duy trì tối thiểu 500.000đ trong ví.'
            ], 400);
        }

        // Deduct balance and create pending transaction
        $vi->so_du -= $amount;
        $vi->save();

        $giaoDich = \App\Models\LichSuGiaoDich::create([
            'ma_vi' => $vi->id,
            'so_tien' => $amount,
            'loai_giao_dich' => 'rut_tien',
            'trang_thai' => 'dang_xu_ly',
            'ma_don_hang' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Yêu cầu rút tiền đang được xử lý.',
            'transaction_id' => $giaoDich->id
        ]);
    }

    public function simulateWithdrawSuccess($id, Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'worker') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $giaoDich = \App\Models\LichSuGiaoDich::where('id', $id)
            ->whereHas('viDienTu', function ($query) use ($user) {
                $query->where('ma_tho', $user->id);
            })
            ->where('loai_giao_dich', 'rut_tien')
            ->where('trang_thai', 'dang_xu_ly')
            ->first();

        if (!$giaoDich) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy giao dịch hoặc đã được xử lý.'], 404);
        }

        $giaoDich->trang_thai = 'thanh_cong';
        $giaoDich->save();

        return response()->json([
            'success' => true,
            'message' => 'Rút tiền thành công.',
        ]);
    }

    private function processTestWalletDeposit($userId, $totalAmount, $txnRef)
    {
        // Mock success deposit directly
        $vi = \App\Models\ViDienTu::firstOrCreate(['ma_tho' => $userId], ['so_du' => 0, 'trang_thai' => 'hoat_dong']);
        $vi->so_du += $totalAmount;
        $vi->save();

        \App\Models\LichSuGiaoDich::create([
            'ma_vi' => $vi->id,
            'so_tien' => $totalAmount,
            'loai_giao_dich' => 'nap_tien',
            'ma_don_hang' => null
        ]);

        return response()->json(['success' => true, 'payment_status' => 'success', 'message' => 'Nap tien test thanh cong.']);
    }

    private function processSuccessWalletDeposit(string $txnRef, float $amount): void
    {
        $parts = explode('_', $txnRef);
        // For VNPay/MoMo: WALLET_{userId}_{timestamp}
        $userId = isset($parts[1]) ? (int) $parts[1] : 0;

        if ($userId <= 0) {
            return;
        }

        $lockKey = 'wallet_deposit_' . $txnRef;
        if (Cache::has($lockKey)) {
            return;
        }

        $vi = \App\Models\ViDienTu::firstOrCreate(
            ['ma_tho' => $userId],
            ['so_du' => 0, 'trang_thai' => 'hoat_dong']
        );
        $vi->so_du += $amount;
        $vi->save();

        \App\Models\LichSuGiaoDich::create([
            'ma_vi' => $vi->id,
            'so_tien' => $amount,
            'loai_giao_dich' => 'nap_tien',
            'ma_don_hang' => null,
        ]);

        Cache::put($lockKey, true, now()->addDays(1));
    }

    private function buildGenericVnpayUrl(Request $request, $txnRef, $totalAmount, $orderInfo)
    {
        $tmnCode = (string) env('VNP_TMN_CODE', '');
        $hashSecret = (string) env('VNP_HASH_SECRET', '');
        $gatewayUrl = (string) env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        if ($tmnCode === '' || $hashSecret === '') return response()->json(['success' => false, 'message' => 'VNPay chua duoc cau hinh.'], 500);

        $createDate = now()->setTimezone(env('VNP_TIMEZONE', 'Asia/Ho_Chi_Minh'));
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) round($totalAmount * 100),
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_ExpireDate' => $createDate->copy()->addMinutes(15)->format('YmdHis'),
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $this->buildCallbackUrl($request, 'vnpay-return'),
            'vnp_TxnRef' => $txnRef,
        ];
        $query = $this->buildVnpayQuery($params);
        $secureHash = $this->buildVnpaySecureHash($params, $hashSecret);
        return response()->json(['success' => true, 'url' => $gatewayUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash]);
    }

    private function buildGenericMomoUrl(Request $request, $txnRef, $totalAmount, $orderInfo, $requestType)
    {
        $partnerCode = (string) env('MOMO_PARTNER_CODE', '');
        $accessKey = (string) env('MOMO_ACCESS_KEY', '');
        $secretKey = (string) env('MOMO_SECRET_KEY', '');
        if ($partnerCode === '' || $accessKey === '' || $secretKey === '') return response()->json(['success' => false, 'message' => 'MoMo chua cau hinh.'], 500);

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'ThoTotNTU',
            'storeId' => 'ThoTotNTUStore',
            'requestId' => (string) time(),
            'amount' => (string) round($totalAmount),
            'orderId' => $txnRef,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->buildCallbackUrl($request, 'momo-return'),
            'ipnUrl' => $this->buildCallbackUrl($request, 'momo-ipn'),
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => $requestType,
        ];
        $rawHash = "accessKey={$accessKey}&amount={$payload['amount']}&extraData={$payload['extraData']}&ipnUrl={$payload['ipnUrl']}&orderId={$payload['orderId']}&orderInfo={$payload['orderInfo']}&partnerCode={$payload['partnerCode']}&redirectUrl={$payload['redirectUrl']}&requestId={$payload['requestId']}&requestType={$payload['requestType']}";
        $payload['signature'] = hash_hmac('sha256', $rawHash, $secretKey);

        $response = Http::withOptions(HttpClientTlsConfig::options())->acceptJson()->post(env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'), $payload);
        if ($response->successful() && !empty($response->json('payUrl'))) return response()->json(['success' => true, 'url' => $response->json('payUrl')]);
        return response()->json(['success' => false, 'message' => 'Loi MoMo.'], 500);
    }

    private function buildGenericZalopayUrl(Request $request, $txnRef, $totalAmount, $orderInfo, $userId)
    {
        $appId = (string) env('ZALOPAY_APP_ID', '');
        $key1 = (string) env('ZALOPAY_KEY1', '');
        if ($appId === '' || $key1 === '') return response()->json(['success' => false, 'message' => 'ZaloPay chua cau hinh.'], 500);

        $createdAt = now()->setTimezone(env('ZALOPAY_TIMEZONE', 'Asia/Ho_Chi_Minh'));
        $appTransId = $createdAt->format('ymd') . '_' . $txnRef;
        $order = [
            'app_id' => $appId,
            'app_time' => (string) $createdAt->valueOf(),
            'app_trans_id' => $appTransId,
            'app_user' => 'user_' . $userId,
            'item' => '[]',
            'embed_data' => json_encode(['redirecturl' => $this->buildCallbackUrl($request, 'zalopay-return')], JSON_UNESCAPED_SLASHES),
            'amount' => (string) round($totalAmount),
            'description' => $orderInfo,
            'bank_code' => '',
            'callback_url' => $this->buildCallbackUrl($request, 'zalopay-ipn'),
        ];
        $order['mac'] = $this->buildZalopayCreateOrderMac($order, $key1);

        $response = Http::withOptions(HttpClientTlsConfig::options())->asForm()->post(env('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create'), $order);
        if ($response->successful() && !empty($response->json('order_url'))) return response()->json(['success' => true, 'url' => $response->json('order_url')]);
        return response()->json(['success' => false, 'message' => 'Loi ZaloPay.'], 500);
    }

    public function vnpayReturn(Request $request)

    {
        $inputData = $this->extractPrefixedFields($request, 'vnp_');
        $receivedHash = (string) $request->input('vnp_SecureHash', '');

        $secret = (string) env('VNP_HASH_SECRET', '');
        $calculatedHash = $this->buildVnpaySecureHash($inputData, $secret);

        $txnRef = (string) $request->input('vnp_TxnRef', '');

        if (!hash_equals($calculatedHash, $receivedHash)) {
            return str_starts_with($txnRef, 'WALLET_') ? redirect('/worker/profile?payment=invalid_signature') : redirect('/customer/my-bookings?payment=invalid_signature');
        }

        if ($request->input('vnp_ResponseCode') !== '00') {
            return str_starts_with($txnRef, 'WALLET_') ? redirect('/worker/profile?payment=failed') : redirect('/customer/my-bookings?payment=failed');
        }

        if (str_starts_with($txnRef, 'WALLET_')) {
            $this->processSuccessWalletDeposit($txnRef, ((float) $request->input('vnp_Amount', 0)) / 100);
            return redirect('/worker/profile?payment=success');
        }

        $bookingId = (int) explode('_', $txnRef)[0];

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

        $secret = (string) env('VNP_HASH_SECRET', '');
        $calculatedHash = $this->buildVnpaySecureHash($inputData, $secret);

        if (!hash_equals($calculatedHash, $receivedHash)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $txnRef = (string) $request->input('vnp_TxnRef', '');

        if ($request->input('vnp_ResponseCode') === '00') {
            if (str_starts_with($txnRef, 'WALLET_')) {
                $this->processSuccessWalletDeposit($txnRef, ((float) $request->input('vnp_Amount', 0)) / 100);
                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }

            $bookingId = (int) explode('_', $txnRef)[0];
            $booking = DonDatLich::find($bookingId);

            if (!$booking) {
                return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
            }

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

        if (!hash_equals($signature, $receivedSignature)) {
            return str_starts_with($orderId, 'WALLET_') ? redirect('/worker/profile?payment=invalid_signature') : redirect('/customer/my-bookings?payment=invalid_signature');
        }

        if ((string) $request->input('resultCode') !== '0') {
            return str_starts_with($orderId, 'WALLET_') ? redirect('/worker/profile?payment=failed') : redirect('/customer/my-bookings?payment=failed');
        }

        if (str_starts_with($orderId, 'WALLET_')) {
            $this->processSuccessWalletDeposit($orderId, (float) $request->input('amount', 0));
            return redirect('/worker/profile?payment=success');
        }

        $bookingId = (int) explode('_', $orderId)[0];

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

        if ((string) $request->input('resultCode') === '0') {
            if (str_starts_with($orderId, 'WALLET_')) {
                $this->processSuccessWalletDeposit($orderId, (float) $request->input('amount', 0));
                return response()->json([], 204);
            }

            $bookingId = (int) explode('_', $orderId)[0];
            $booking = DonDatLich::find($bookingId);

            if (!$booking) {
                return response()->json(['message' => 'Order not found'], 404);
            }

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
        $query = [
            'appid' => (string) $request->input('appid', ''),
            'apptransid' => (string) $request->input('apptransid', ''),
            'pmcid' => (string) $request->input('pmcid', ''),
            'bankcode' => (string) $request->input('bankcode', ''),
            'amount' => (string) $request->input('amount', ''),
            'discountamount' => (string) $request->input('discountamount', ''),
            'status' => (string) $request->input('status', ''),
        ];
        $receivedChecksum = (string) $request->input('checksum', '');
        $key2 = trim((string) env('ZALOPAY_KEY2', ''));
        $apptransid = (string) $request->input('apptransid', '');
        $isWallet = str_contains($apptransid, '_WALLET_');

        if ($receivedChecksum === '' || $key2 === '' || !hash_equals($this->buildZalopayRedirectChecksum($query, $key2), $receivedChecksum)) {
            return $isWallet ? redirect('/worker/profile?payment=invalid_signature') : redirect('/customer/my-bookings?payment=invalid_signature');
        }

        if ($query['status'] !== '1') {
            return $isWallet ? redirect('/worker/profile?payment=failed') : redirect('/customer/my-bookings?payment=failed');
        }

        $queryResult = $this->queryZalopayOrderStatus($apptransid);
        if (($queryResult['success'] ?? false) !== true) {
            $status = ($queryResult['processing'] ?? false) ? 'processing' : 'failed';
            return $isWallet ? redirect('/worker/profile?payment=' . $status) : redirect('/customer/my-bookings?payment=' . $status);
        }

        $fallbackAmount = (float) ($query['amount'] !== '' ? $query['amount'] : '0');
        $amount = (float) ($queryResult['amount'] ?? $fallbackAmount);

        if ($isWallet) {
            $parts = explode('_', $apptransid);
            $userId = isset($parts[2]) ? (int) $parts[2] : 0;
            $txnRef = 'WALLET_' . $userId . '_' . (isset($parts[3]) ? $parts[3] : time());
            $this->processSuccessWalletDeposit($txnRef, $amount);
            return redirect('/worker/profile?payment=success');
        }

        $parts = explode('_', $apptransid);
        $bookingId = isset($parts[1]) ? (int) $parts[1] : 0;
        if ($bookingId <= 0) {
            return redirect('/customer/my-bookings?payment=failed');
        }

        $this->processSuccessPayment(
            $bookingId,
            $amount,
            'zalopay',
            (string) ($queryResult['zp_trans_id'] ?? $apptransid),
            $queryResult['raw'] ?? $request->query()
        );

        return redirect('/customer/my-bookings?payment=success');
    }

    public function zalopayIpn(Request $request)
    {
        $key2 = trim((string) env('ZALOPAY_KEY2', ''));
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || empty($payload['data']) || empty($payload['mac'])) {
            return response()->json(['return_code' => 2, 'return_message' => 'invalid payload']);
        }

        $calculatedMac = hash_hmac('sha256', $payload['data'], $key2);
        if (!hash_equals($calculatedMac, (string) $payload['mac'])) {
            return response()->json(['return_code' => 2, 'return_message' => 'mac not equal']);
        }

        $data = json_decode($payload['data'], true);
        $apptransid = (string) ($data['app_trans_id'] ?? '');
        $isWallet = str_contains($apptransid, '_WALLET_');

        if ($isWallet) {
            $parts = explode('_', $apptransid);
            $userId = isset($parts[2]) ? (int) $parts[2] : 0;
            $txnRef = 'WALLET_' . $userId . '_' . (isset($parts[3]) ? $parts[3] : time());
            $this->processSuccessWalletDeposit($txnRef, (float) ($data['amount'] ?? 0));
            return response()->json(['return_code' => 1, 'return_message' => 'success']);
        }

        $parts = explode('_', $apptransid);
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
        $bankCode = trim((string) env('VNP_BANK_CODE', ''));
        $expireMinutes = max(1, (int) env('VNP_EXPIRE_MINUTES', 15));
        $vnpTimezone = trim((string) env('VNP_TIMEZONE', 'Asia/Ho_Chi_Minh'));

        if ($tmnCode === '' || $hashSecret === '') {
            return response()->json([
                'success' => false,
                'message' => 'VNPay chua duoc cau hinh trong file .env.',
            ], 500);
        }

        $txnRef = $booking->id . '_' . time();
        $createDate = now()->setTimezone($vnpTimezone);
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) round($totalAmount * 100),
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $createDate->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_ExpireDate' => $createDate->copy()->addMinutes($expireMinutes)->format('YmdHis'),
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don dat lich ma ' . $booking->id,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $this->buildCallbackUrl($request, 'vnpay-return'),
            'vnp_TxnRef' => $txnRef,
        ];

        if ($bankCode !== '') {
            $params['vnp_BankCode'] = $bankCode;
        }

        $query = $this->buildVnpayQuery($params);
        $secureHash = $this->buildVnpaySecureHash($params, $hashSecret);
        $url = $gatewayUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    private function createMomoWalletPayment(Request $request, DonDatLich $booking, float $totalAmount)
    {
        return $this->createMomoPaymentRequest(
            $request,
            $booking,
            $totalAmount,
            'captureWallet',
            'Thanh toan don dat lich #' . $booking->id,
        );
    }

    private function createMomoAtmPayment(Request $request, DonDatLich $booking, float $totalAmount)
    {
        if (round($totalAmount) < 10000) {
            return response()->json([
                'success' => false,
                'message' => 'MoMo ATM/test card chi ho tro giao dich tu 10.000đ tro len.',
            ], 422);
        }

        return $this->createMomoPaymentRequest(
            $request,
            $booking,
            $totalAmount,
            'payWithATM',
            'Thanh toan don dat lich #' . $booking->id . ' qua MoMo ATM',
        );
    }

    private function createMomoPaymentRequest(
        Request $request,
        DonDatLich $booking,
        float $totalAmount,
        string $requestType,
        string $orderInfo
    )
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
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => $requestType,
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
            $response = Http::withOptions(HttpClientTlsConfig::options())
                ->acceptJson()
                ->post($endpoint, $payload);
            $json = $response->json();

            if ($response->successful() && !empty($json['payUrl'])) {
                return response()->json([
                    'success' => true,
                    'url' => $json['payUrl'],
                ]);
            }

            Log::error('MoMo create payment failed', [
                'request_type' => $requestType,
                'status' => $response->status(),
                'response' => $json,
            ]);

            return response()->json([
                'success' => false,
                'message' => $json['message'] ?? 'Khong tao duoc giao dich MoMo.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('MoMo request error', [
                'request_type' => $requestType,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Khong ket noi duoc cong thanh toan MoMo.',
            ], 500);
        }
    }

    private function createZalopayPayment(Request $request, DonDatLich $booking, float $totalAmount)
    {
        $appId = trim((string) env('ZALOPAY_APP_ID', ''));
        $key1 = trim((string) env('ZALOPAY_KEY1', ''));
        $endpoint = trim((string) env('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create'));
        $timezone = trim((string) env('ZALOPAY_TIMEZONE', 'Asia/Ho_Chi_Minh'));
        $expireDurationSeconds = min(2592000, max(300, (int) env('ZALOPAY_EXPIRE_DURATION_SECONDS', 900)));
        $bankCode = trim((string) env('ZALOPAY_BANK_CODE', ''));
        $preferredPaymentMethods = $this->resolveZalopayPreferredPaymentMethods();

        if ($appId === '' || $key1 === '') {
            return response()->json([
                'success' => false,
                'message' => 'ZaloPay chua duoc cau hinh trong file .env.',
            ], 500);
        }

        $createdAt = now()->setTimezone($timezone);
        $appTime = (string) $createdAt->valueOf();
        $appTransId = $createdAt->format('ymd') . '_' . $booking->id . '_' . $createdAt->timestamp;
        $embedData = json_encode([
            'redirecturl' => $this->buildCallbackUrl($request, 'zalopay-return'),
            'preferred_payment_method' => $preferredPaymentMethods,
        ], JSON_UNESCAPED_SLASHES);
        $item = json_encode([['booking_id' => (int) $booking->id]], JSON_UNESCAPED_SLASHES);

        $order = [
            'app_id' => $appId,
            'app_time' => $appTime,
            'app_trans_id' => $appTransId,
            'app_user' => 'user_' . ($booking->khach_hang_id ?? 'guest'),
            'item' => $item,
            'embed_data' => $embedData,
            'amount' => (string) ((int) round($totalAmount)),
            'description' => 'Thanh toan don dat lich ma ' . $booking->id,
            'bank_code' => $bankCode,
            'callback_url' => $this->buildCallbackUrl($request, 'zalopay-ipn'),
            'expire_duration_seconds' => (string) $expireDurationSeconds,
        ];
        $order['mac'] = $this->buildZalopayCreateOrderMac($order, $key1);

        try {
            $response = Http::withOptions(HttpClientTlsConfig::options())
                ->asForm()
                ->post($endpoint, $order);
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
                'app_id' => $appId,
                'app_trans_id' => $appTransId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $json['sub_return_message'] ?? $json['return_message'] ?? 'Khong tao duoc giao dich ZaloPay.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('ZaloPay request error', ['exception' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Khong ket noi duoc cong thanh toan ZaloPay.',
            ], 500);
        }
    }

    private function createTestPayment(DonDatLich $booking, float $totalAmount)
    {
        $transactionId = 'TEST_' . $booking->id . '_' . now()->format('YmdHis');

        $this->processSuccessPayment(
            (int) $booking->id,
            $totalAmount,
            'test',
            $transactionId,
            [
                'mode' => 'test',
                'note' => 'Thanh toan test noi bo, khong tao giao dich that.',
                'paid_at' => now()->toIso8601String(),
            ]
        );

        return response()->json([
            'success' => true,
            'payment_status' => 'success',
            'message' => 'Thanh toan test thanh cong. Don da duoc hoan tat.',
        ]);
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

        $booking->thoi_gian_hoan_thanh = $booking->thoi_gian_hoan_thanh ?? now();
        $booking->trang_thai_thanh_toan = true;
        $booking->save();

        app(\App\Services\Chat\AiKnowledgeSyncService::class)->syncBookingCases($bookingId);
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
        $baseUrl = trim((string) env('PAYMENT_CALLBACK_BASE_URL', ''));

        if ($baseUrl === '') {
            $baseUrl = trim((string) env('APP_URL', ''));
        }

        if ($baseUrl === '') {
            $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        }

        return rtrim($baseUrl, '/') . '/api/payment/' . ltrim($suffix, '/');
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

    /**
     * @param array<string, mixed> $order
     */
    private function buildZalopayCreateOrderMac(array $order, string $key1): string
    {
        $data = (string) ($order['app_id'] ?? '')
            . '|' . (string) ($order['app_trans_id'] ?? '')
            . '|' . (string) ($order['app_user'] ?? '')
            . '|' . (string) ($order['amount'] ?? '')
            . '|' . (string) ($order['app_time'] ?? '')
            . '|' . (string) ($order['embed_data'] ?? '')
            . '|' . (string) ($order['item'] ?? '');

        return hash_hmac('sha256', $data, $key1);
    }

    /**
     * @param array<string, string> $query
     */
    private function buildZalopayRedirectChecksum(array $query, string $key2): string
    {
        $data = ($query['appid'] ?? '')
            . '|' . ($query['apptransid'] ?? '')
            . '|' . ($query['pmcid'] ?? '')
            . '|' . ($query['bankcode'] ?? '')
            . '|' . ($query['amount'] ?? '')
            . '|' . ($query['discountamount'] ?? '')
            . '|' . ($query['status'] ?? '');

        return hash_hmac('sha256', $data, $key2);
    }

    /**
     * @return array{success: bool, processing: bool, amount?: float|null, zp_trans_id?: string|null, raw?: array<string, mixed>}
     */
    private function queryZalopayOrderStatus(string $appTransId): array
    {
        $appId = trim((string) env('ZALOPAY_APP_ID', ''));
        $key1 = trim((string) env('ZALOPAY_KEY1', ''));
        $endpoint = trim((string) env('ZALOPAY_QUERY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/query'));

        if ($appId === '' || $key1 === '' || $appTransId === '') {
            return ['success' => false, 'processing' => false];
        }

        $payload = [
            'app_id' => $appId,
            'app_trans_id' => $appTransId,
        ];
        $payload['mac'] = hash_hmac('sha256', $payload['app_id'] . '|' . $payload['app_trans_id'] . '|' . $key1, $key1);

        try {
            $response = Http::withOptions(HttpClientTlsConfig::options())
                ->asForm()
                ->post($endpoint, $payload);
            $json = $response->json();

            if (!$response->successful() || !is_array($json)) {
                Log::error('ZaloPay query order failed', [
                    'status' => $response->status(),
                    'response' => $json,
                    'app_trans_id' => $appTransId,
                ]);

                return ['success' => false, 'processing' => false];
            }

            return [
                'success' => (int) ($json['return_code'] ?? 0) === 1,
                'processing' => (int) ($json['return_code'] ?? 0) === 3 || (bool) ($json['is_processing'] ?? false),
                'amount' => isset($json['amount']) ? (float) $json['amount'] : null,
                'zp_trans_id' => isset($json['zp_trans_id']) ? (string) $json['zp_trans_id'] : null,
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('ZaloPay query order error', [
                'exception' => $e->getMessage(),
                'app_trans_id' => $appTransId,
            ]);

            return ['success' => false, 'processing' => false];
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveZalopayPreferredPaymentMethods(): array
    {
        $configured = trim((string) env('ZALOPAY_PREFERRED_PAYMENT_METHODS', 'zalopay_wallet'));
        if ($configured === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', $configured)
        )));
    }

    /**
     * VNPAY signs query data using application/x-www-form-urlencoded encoding.
     *
     * @param array<string, mixed> $params
     */
    private function buildVnpayQuery(array $params): string
    {
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        return http_build_query($params, '', '&', PHP_QUERY_RFC1738);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildVnpaySecureHash(array $params, string $secret): string
    {
        return hash_hmac('sha512', $this->buildVnpayQuery($params), $secret);
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
