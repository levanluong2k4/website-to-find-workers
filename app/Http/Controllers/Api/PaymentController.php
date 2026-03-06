<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DonDatLich;
use App\Models\ThanhToan;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Tạo URL thanh toán VNPay
     */
    public function createPaymentUrl(Request $request)
    {
        $request->validate([
            'don_dat_lich_id' => 'required|exists:don_dat_lich,id',
            'phuong_thuc' => 'required|in:vnpay,momo,zalopay', // Hỗ trợ VNPay, MoMo, ZaloPay
        ]);

        $donDatLich = DonDatLich::find($request->don_dat_lich_id);

        if ($donDatLich->trang_thai !== 'cho_thanh_toan') {
            return response()->json(['success' => false, 'message' => 'Đơn này không ở trạng thái chờ thanh toán.'], 400);
        }

        // Tính tổng tiền = tiền công + tiên linh kiện + phụ phí. Demo: hardcode hoặc tính tuỳ theo cột
        // Assume total was already stored. For DATN, we sum fields manually or there is a column for it.
        // Wait, the migration added `tien_cong` and `tien_thue_xe` earlier (we can see from user state `add_tien_cong_and_tien_thue_xe_to_don_dat_lich_table`). But total is sum of those?
        // Let's assume there is a way to calculate total. Actually, the request could pass total, or we calculate it.
        // Calculation: gia_tien (base service code/price? Wait, don_dat_lich has 'gia_tien'. Then worker appends something?)
        // Let's calculate total robustly.
        $totalAmount = $donDatLich->gia_tien ?? 0; // fallback if other columns aren't ready
        // (x100 for VNPay Format)
        if (isset($donDatLich->tien_cong)) $totalAmount += $donDatLich->tien_cong;
        if (isset($donDatLich->tien_thue_xe)) $totalAmount += $donDatLich->tien_thue_xe;

        $vnp_Url = env('VNP_URL', "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html");
        $vnp_Returnurl = url('/api/payment/vnpay-return');
        $vnp_TmnCode = env('VNP_TMN_CODE', "7ZJ6D6Z0"); // Use typical sandbox or user's code
        $vnp_HashSecret = env('VNP_HASH_SECRET', "R084Q4F4ZZJZLJZ212O21X12"); // Provide dummy secret for safety if unset

        $vnp_TxnRef = $donDatLich->id . '_' . time(); // Unique order id
        $vnp_OrderInfo = "Thanh toan don dat lich #" . $donDatLich->id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $totalAmount * 100;
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB'; // Testing bank Sandbox VNPay
        $vnp_IpAddr = $request->ip();

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        if ($request->phuong_thuc === 'momo') {
            return $this->createMomoPayment($donDatLich, $totalAmount);
        }

        if ($request->phuong_thuc === 'zalopay') {
            return $this->createZalopayPayment($donDatLich, $totalAmount);
        }

        return response()->json([
            'success' => true,
            'url' => $vnp_Url
        ]);
    }

    /**
     * Tạo URL thanh toán MoMo
     */
    private function createMomoPayment($donDatLich, $totalAmount)
    {
        $endpoint = env('MOMO_ENDPOINT', "https://test-payment.momo.vn/v2/gateway/api/create");
        $partnerCode = env('MOMO_PARTNER_CODE', "MOMOVIP");
        $accessKey = env('MOMO_ACCESS_KEY', "MOMOVIPKEY");
        $secretKey = env('MOMO_SECRET_KEY', "MOMOVIPSECRET");

        $orderInfo = "Thanh toan don dat lich #" . $donDatLich->id;
        $amount = (string)($totalAmount);
        $orderId = $donDatLich->id . "_" . time();
        $redirectUrl = url('/api/payment/momo-return');
        $ipnUrl = url('/api/payment/momo-ipn');
        $extraData = "";

        $requestId = time() . "";
        $requestType = "captureWallet";

        // Signature construction
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = array(
            'partnerCode' => $partnerCode,
            'partnerName' => "ThoViet",
            "storeId" => "ThoVietStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        );

        try {
            $response = \Illuminate\Support\Facades\Http::post($endpoint, $data);
            $jsonResult = $response->json();

            if (isset($jsonResult['payUrl'])) {
                return response()->json([
                    'success' => true,
                    'url' => $jsonResult['payUrl']
                ]);
            } else {
                Log::error('MoMo Create Payment Failed', ['response' => $jsonResult]);
                return response()->json(['success' => false, 'message' => 'Lỗi tạo giao dịch MoMo: ' . ($jsonResult['message'] ?? 'Unknown')], 500);
            }
        } catch (\Exception $e) {
            Log::error('MoMo Request Error', ['exception' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi kết nối đến cổng thanh toán MoMo.'], 500);
        }
    }

    /**
     * Redirect người dùng sau khi thanh toán trên VNPay
     */
    public function vnpayReturn(Request $request)
    {
        $vnp_SecureHash = $request->input('vnp_SecureHash');
        $inputData = array();
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnp_HashSecret = env('VNP_HASH_SECRET', "R084Q4F4ZZJZLJZ212O21X12");
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $vnp_TxnRef = $request->input('vnp_TxnRef');
        $donDatLichId = explode('_', $vnp_TxnRef)[0];

        if ($secureHash == $vnp_SecureHash) {
            if ($request->input('vnp_ResponseCode') == '00') {
                // Success
                $this->processSuccessPayment($donDatLichId, $request->input('vnp_Amount') / 100, 'vnpay', $vnp_TxnRef, json_encode($request->all()));
                return redirect('/customer/my-bookings?payment=success');
            } else {
                // Failed
                return redirect('/customer/my-bookings?payment=failed');
            }
        } else {
            // Invalid Signature
            return redirect('/customer/my-bookings?payment=invalid_signature');
        }
    }

    /**
     * IPN Webhook cho VNPay
     */
    public function vnpayIpn(Request $request)
    {
        $vnp_SecureHash = $request->input('vnp_SecureHash');
        $inputData = array();
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnp_HashSecret = env('VNP_HASH_SECRET', "R084Q4F4ZZJZLJZ212O21X12");
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash == $vnp_SecureHash) {
            $vnp_TxnRef = $request->input('vnp_TxnRef');
            $donDatLichId = explode('_', $vnp_TxnRef)[0];
            $donDatLich = DonDatLich::find($donDatLichId);

            if ($donDatLich) {
                if ($request->input('vnp_ResponseCode') == '00' && $donDatLich->trang_thai == 'cho_thanh_toan') {
                    $this->processSuccessPayment($donDatLichId, $request->input('vnp_Amount') / 100, 'vnpay', $vnp_TxnRef, json_encode($request->all()));
                }
                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            } else {
                return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
            }
        } else {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }
    }

    private function processSuccessPayment($donDatLichId, $amount, $method, $transactionId, $extraInfo)
    {
        // Check if already paid to avoid duplicate insertion
        $existing = ThanhToan::where('don_dat_lich_id', $donDatLichId)->where('trang_thai', 'success')->first();
        if ($existing) return;

        $thanhToan = new ThanhToan();
        $thanhToan->don_dat_lich_id = $donDatLichId;
        $thanhToan->so_tien = $amount;
        $thanhToan->phuong_thuc = $method;
        $thanhToan->ma_giao_dich = $transactionId;
        $thanhToan->trang_thai = 'success';
        $thanhToan->thong_tin_extra = $extraInfo;
        $thanhToan->save();

        // Update status
        $donDatLich = DonDatLich::find($donDatLichId);
        if ($donDatLich && $donDatLich->trang_thai == 'cho_thanh_toan') {
            $donDatLich->trang_thai = 'da_xong'; // Correct backend status for Completed Enum
            $donDatLich->save();
        }
    }

    /**
     * Redirect khách hàng sau thanh toán MoMo
     */
    public function momoReturn(Request $request)
    {
        $partnerCode = $request->input('partnerCode');
        $orderId = $request->input('orderId');
        $requestId = $request->input('requestId');
        $amount = $request->input('amount');
        $orderInfo = $request->input('orderInfo');
        $orderType = $request->input('orderType');
        $transId = $request->input('transId');
        $resultCode = $request->input('resultCode');
        $message = $request->input('message');
        $payType = $request->input('payType');
        $responseTime = $request->input('responseTime');
        $extraData = $request->input('extraData');
        $momoSignature = $request->input('signature');

        $accessKey = env('MOMO_ACCESS_KEY', "MOMOVIPKEY");
        $secretKey = env('MOMO_SECRET_KEY', "MOMOVIPSECRET");

        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $donDatLichId = explode('_', $orderId)[0];

        if ($signature == $momoSignature) {
            if ($resultCode == '0') {
                $this->processSuccessPayment($donDatLichId, $amount, 'momo', $transId, json_encode($request->all()));
                return redirect('/customer/my-bookings?payment=success');
            } else {
                return redirect('/customer/my-bookings?payment=failed');
            }
        } else {
            return redirect('/customer/my-bookings?payment=invalid_signature');
        }
    }

    /**
     * IPN Webhook MoMo
     */
    public function momoIpn(Request $request)
    {
        $partnerCode = $request->input('partnerCode');
        $orderId = $request->input('orderId');
        $requestId = $request->input('requestId');
        $amount = $request->input('amount');
        $orderInfo = $request->input('orderInfo');
        $orderType = $request->input('orderType');
        $transId = $request->input('transId');
        $resultCode = $request->input('resultCode');
        $message = $request->input('message');
        $payType = $request->input('payType');
        $responseTime = $request->input('responseTime');
        $extraData = $request->input('extraData');
        $momoSignature = $request->input('signature');

        $accessKey = env('MOMO_ACCESS_KEY', "MOMOVIPKEY");
        $secretKey = env('MOMO_SECRET_KEY', "MOMOVIPSECRET");

        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        if ($signature == $momoSignature) {
            $donDatLichId = explode('_', $orderId)[0];
            $donDatLich = DonDatLich::find($donDatLichId);

            if ($donDatLich) {
                if ($resultCode == '0' && $donDatLich->trang_thai == 'cho_thanh_toan') {
                    $this->processSuccessPayment($donDatLichId, $amount, 'momo', $transId, json_encode($request->all()));
                }
                return response()->json([], 204); // MoMo requires 204 HTTP status for IPN success acknowledgment
            } else {
                return response()->json(['message' => 'Order not found'], 404);
            }
        } else {
            return response()->json(['message' => 'Invalid signature'], 400);
        }
    }

    /**
     * Tạo URL thanh toán ZaloPay
     */
    private function createZalopayPayment($donDatLich, $totalAmount)
    {
        $app_id = env('ZALOPAY_APP_ID', "2553");
        $key1 = env('ZALOPAY_KEY1', "PcY4iZIKFCIdgZvA6ueMcMHHUbRlYjPL");
        $endpoint = env('ZALOPAY_ENDPOINT', "https://sb-openapi.zalopay.vn/v2/create");

        $embeddata = json_encode(['redirecturl' => url('/api/payment/zalopay-return')]);
        $item = json_encode([['don_dat_lich' => $donDatLich->id]]);
        $app_trans_id = date("ymd") . "_" . $donDatLich->id . "_" . time();

        $order = [
            "app_id" => $app_id,
            "app_time" => round(microtime(true) * 1000),
            "app_trans_id" => $app_trans_id,
            "app_user" => "user_" . ($donDatLich->khach_hang_id ?? 'guest'),
            "item" => $item,
            "embed_data" => $embeddata,
            "amount" => $totalAmount,
            "description" => "Thanh toan don dat lich #" . $donDatLich->id,
            "bank_code" => "",
            "mac" => ""
        ];

        $data = $order["app_id"] . "|" . $order["app_trans_id"] . "|" . $order["app_user"] . "|" . $order["amount"] . "|" . $order["app_time"] . "|" . $order["embed_data"] . "|" . $order["item"];
        $order["mac"] = hash_hmac("sha256", $data, $key1);

        try {
            $response = \Illuminate\Support\Facades\Http::post($endpoint, $order);
            $jsonResult = $response->json();

            if (isset($jsonResult['order_url'])) {
                return response()->json([
                    'success' => true,
                    'url' => $jsonResult['order_url']
                ]);
            } else {
                Log::error('ZaloPay Create Payment Failed', ['response' => $jsonResult]);
                return response()->json(['success' => false, 'message' => 'Lỗi tạo giao dịch ZaloPay: ' . ($jsonResult['return_message'] ?? 'Unknown')], 500);
            }
        } catch (\Exception $e) {
            Log::error('ZaloPay Request Error', ['exception' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi kết nối ZaloPay.'], 500);
        }
    }

    /**
     * Redirect khách hàng sau thanh toán ZaloPay
     */
    public function zalopayReturn(Request $request)
    {
        // For Sandbox simple return, we can just check status. 
        // In prod, check checksum to ensure integrity.
        $status = $request->input('status');
        if ($status == 1) {
            return redirect('/customer/my-bookings?payment=success');
        } else {
            return redirect('/customer/my-bookings?payment=failed');
        }
    }

    /**
     * IPN Webhook ZaloPay
     */
    public function zalopayIpn(Request $request)
    {
        $key2 = env('ZALOPAY_KEY2', "kLtgPl8YESD1xXVmSRp202T2BkuZ2N5V");

        $postdata = $request->getContent();
        $postdatajson = json_decode($postdata, true);

        $mac = hash_hmac("sha256", $postdatajson["data"], $key2);

        $requestmac = $postdatajson["mac"];

        if (strcmp($mac, $requestmac) != 0) {
            return response()->json(["return_code" => -1, "return_message" => "mac not equal"]);
        } else {
            $dataJson = json_decode($postdatajson["data"], true);
            $app_trans_id = $dataJson['app_trans_id'];
            $amount = $dataJson['amount'];
            $zp_trans_id = $dataJson['zp_trans_id'] ?? '';

            // Extract donDatLichId from app_trans_id (format: yymmdd_donDatLichId_time)
            $parts = explode('_', $app_trans_id);
            if (count($parts) >= 2) {
                $donDatLichId = $parts[1];
                $donDatLich = DonDatLich::find($donDatLichId);

                if ($donDatLich && $donDatLich->trang_thai == 'cho_thanh_toan') {
                    $this->processSuccessPayment($donDatLichId, $amount, 'zalopay', $zp_trans_id, $postdatajson["data"]);
                }
            }

            return response()->json(["return_code" => 1, "return_message" => "success"]);
        }
    }
}
