<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Group;
use App\Models\Transaction;
use App\Services\BalanceService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private BalanceService $balances,
        private NotificationService $notifications
    ) {}

    public function index(Request $request)
    {
        $userId = (string) $request->user()->_id;

        $transactions = Transaction::where(function ($q) use ($userId) {
            $q->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
        })->orderByDesc('created_at')->paginate(20);

        return view('transactions.index', compact('transactions'));
    }

    public function initiatePayment(Request $request)
    {
        $data = $request->validate([
            'to_user_id' => 'required|string',
            'group_id'   => 'required|string',
            'amount'     => 'required|integer|min:100', // paise
        ]);

        $group = Group::findOrFail($data['group_id']);
        abort_unless($group->hasMember((string) $request->user()->_id), 403);

        $order = $this->payments->createOrder($data['amount'], 'INR', [
            'from_user_id' => (string) $request->user()->_id,
            'to_user_id'   => $data['to_user_id'],
            'group_id'     => $data['group_id'],
        ]);

        $transaction = Transaction::create([
            'from_user_id'      => (string) $request->user()->_id,
            'to_user_id'        => $data['to_user_id'],
            'group_id'          => $data['group_id'],
            'amount'            => $data['amount'],
            'status'            => 'pending',
            'razorpay_order_id' => $order['order_id'],
        ]);

        return response()->json([
            'transaction_id' => (string) $transaction->_id,
            'order'          => $order,
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'transaction_id'      => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $transaction = Transaction::findOrFail($data['transaction_id']);
        abort_unless($transaction->from_user_id === (string) $request->user()->_id, 403);

        $success = $this->payments->verifyAndCapture($transaction, $data);

        if (!$success) {
            return response()->json(['message' => 'Payment verification failed.'], 422);
        }

        $this->balances->recalculate($transaction->group_id);

        $this->notifications->send(
            $transaction->to_user_id,
            'payment_received',
            $request->user()->name . ' paid you',
            $transaction->amount_formatted . ' payment received.',
            ['transaction_id' => (string) $transaction->_id]
        );

        return response()->json(['message' => 'Payment verified.']);
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature', '');
        $payload   = $request->all();

        $handled = $this->payments->handleWebhook(
            $payload,
            $signature,
            config('services.razorpay.webhook_secret')
        );

        if (!$handled) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $orderId     = $payload['payload']['payment']['entity']['order_id'] ?? null;
        $transaction = Transaction::where('razorpay_order_id', $orderId)->first();

        if ($transaction && $transaction->status === 'completed') {
            $this->balances->recalculate($transaction->group_id);
        }

        return response()->json(['status' => 'ok']);
    }
}
