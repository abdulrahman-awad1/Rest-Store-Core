<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\Payment;
use App\Services\PaymobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymobService $paymob;

    public function __construct(PaymobService $paymob)
    {
        $this->paymob = $paymob;
    }

    public function pay(PaymentRequest $request)
    {
        $order = Order::with(['address', 'user'])
            ->findOrFail($request->order_id);

        if ($order->payment_status === 'paid') {
            abort(400, 'Order already paid');
        }

        $integrationId = $this->paymob->resolveIntegration($request->pay_method);

        $token = $this->paymob->authenticate();

        $paymobOrderId = $this->paymob->createOrder($token, $order);

        $paymentKey = $this->paymob->generatePaymentKey(
            $token,
            $paymobOrderId,
            $order,
            $this->paymob->buildBillingData($order),
            $integrationId
        );

        $payment = DB::transaction(function () use ($order, $request, $paymobOrderId) {
            return Payment::create([
                'order_id'        => $order->id,
                'user_id'         => $order->user_id,
                'provider'        => 'paymob',
                'amount'          => $order->total,
                'method'          => $request->pay_method,
                'status'          => 'pending',
                'paymob_order_id' => $paymobOrderId,
            ]);
        });

        return $this->paymob->dispatchPayment(
            $paymentKey,
            $payment->method,
            $order
        );
    }

    public function callback(Request $request)
    {
        $data = $request->all();
    
        // في التطوير نسمح بالاختبار بدون HMAC
        if (app()->environment('production')) {
            if (!$this->paymob->verifyHmac($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'invalid hmac'
                ], 401);
            }
        }
    
        $payment = Payment::where('paymob_order_id', $data['order'] ?? null)->first();
    
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'payment not found'
            ], 404);
        }
    
        if (($data['success'] ?? false) == true) {
            $payment->update([
                'status'         => 'paid',
                'transaction_id' => $data['id'] ?? null,
                'raw_response'   => $data,
            ]);
    
            $payment->order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);
        } else {
            $payment->update([
                'status'         => 'failed',
                'transaction_id' => $data['id'] ?? null,
                'raw_response'   => $data,
            ]);
        }
    
        return response()->json([
            'success' => true,
            'payment' => $payment->fresh(),
            'order'   => $payment->order->fresh(),
        ]);
    }
    

    public function redirect(Request $request)
{
    Log::info('Paymob redirect', $request->all());

    return response()->json([
        'success' => filter_var($request->get('success'), FILTER_VALIDATE_BOOLEAN),
        'order'   => $request->get('order'),
        'id'      => $request->get('id'),
        'message' => $request->get('success') ? 'payment success' : 'payment failed',
    ]);
}

}
