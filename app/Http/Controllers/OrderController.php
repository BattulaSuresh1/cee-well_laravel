<?php

namespace App\Http\Controllers;

use App\Models\cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\OrderPayments;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {

        $pageSize = $request->per_page ?? 25;

        $columns = ['*'];

        $pageName = 'page';

         $page = $request->current_page ?? 1;

         $search = $request->filter ?? "";

        $query = Order::with('orderitems')
            ->with(['orderpayments'])
            ->withSum('orderpayments','order_payments.paid_amount')
            ->orderBy('id', 'DESC');

        if ($request->has('customerId')) {
            $customerId = $request->customerId;
            if (!empty($customerId))
                $query->where('customer_id', $customerId);
        }

        if ($request->has('userId')) {
            $userId = $request->userId;
            if (!empty($userId))
                $query->where('user_id', $userId);
        }

        $query->where('status', '1');

        if (!empty($search)) {
            $query->where('id', 'LIKE', "%$search%");
            $query->orWhere('amount', 'LIKE', "%$search%");
            // $query->orWhere('paidAmount', 'LIKE' , "%$search%");
        }
        
         $data = $query->paginate($pageSize,$columns,$pageName,$page);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        try {

            $customerId = $request->customerId;
            $userId = $request->userId;
            $cartItems = cart::where(['customer_id' => $customerId, 'status' => 1])->get();
            $order = new Order();
            $order->customer_id = $customerId;
            $order->user_id = $userId;
            $order->order_status = "0";
            $order->save();

            $total = 0;

            foreach ($cartItems as $key => $cart) {
                $orderItem = new OrderItems();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $cart['product_id'];
                $orderItem->prescription_id = $cart['prescription_id'];
                $orderItem->lenses_id = $cart['cart_lenses_id'];
                $orderItem->measurements_id = $cart['cart_measurements_id'];
                $orderItem->quantities = $cart['quantities'];
                $orderItem->price = $cart['price'];
                $orderItem->total_amount = $cart['total_amount'];
                $orderItem->discount = $cart['discount'];
                $orderItem->save();

                if ($orderItem->save()) {
                    cart::where('id', $cart['id'])->delete();

                    $inventory = Inventory::where('product_id', $cart['product_id'])->where('status', '1')->first();

                    if ($inventory) {
                        $inventory->available = ((int) $inventory['available']) - (int) $cart['quantities'];
                        $inventory->save();
                    }



                }

                $total = $total + $cart['total_amount'];
            }

            $orderItem = Order::find($order->id);
            $orderItem->amount = $total;
            $orderItem->save();

            $res = [
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order
            ];

            return response()->json($res);

        } catch (\Exception $e) {
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' => $e->getMessage()
            ];
            return response()->json($res);
        }

    }

    public function show($id)
    {
        try {

            $data['order'] = Order::with('customer')
                ->with(['orderitems.product', 'orderitems.prescription.lenspower', 'orderitems.lens', 'orderitems.measurements.precalvalues', 'orderitems.measurements.thickness'])
                ->with(['orderpayments'])
                ->find($id);
            $res = [
                'success' => true,
                'message' => 'Order details.',
                'data' => $data,
            ];

            return response()->json($res);

        } catch (\Exception $e) {
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' => $e->getMessage()
            ];
            return response()->json($res);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $orderStatus = '1';

            $order = Order::with('customer')
                ->with(['orderitems.product', 'orderitems.prescription.lenspower', 'orderitems.lens', 'orderitems.measurements.precalvalues', 'orderitems.measurements.thickness'])
                ->with(['orderpayments'])->find($id);
            $count = OrderPayments::where(['order_id' => $id])->count();

            if ($count == 0) {

                // $order->payment_type = $request->paymentType;
                $order->payment_settlement = $request->paymentSettlement;
                // $order->paid_amount = $request->amount;
                if ($request->has('orderStatus')) {
                    $order->order_status = $request->orderStatus;
                }
                $order->save();
            }


            $payment = new OrderPayments;
            $payment->order_id = $id;
            $payment->payment_type = $request->paymentType;
            $payment->paid_amount = $request->amount;

            $payment->save();

            $res = [
                'success' => true,
                'message' => 'Order updated successfully.',
                'data' => $order
            ];

            return response()->json($res);

        } catch (\Exception $e) {
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' => $e->getMessage()
            ];
            return response()->json($res);
        }

    }

    public function getOrders(Request $request)
    {

        $pageSize = $request->per_page ?? 25;

        $columns = ['*'];

        $pageName = 'page';

        $page = $request->current_page ?? 1;

        $search = $request->filter ?? "";

        $query = Order::with('orderitems')
        // ->withSum('orderpayments','order_payments.paid_amount')
        ->orderBy('id', 'DESC');
;

        if ($request->has('customerId')) {
            $customerId = $request->customerId;
            if (!empty($customerId))
                $query->where('customer_id', $customerId);
        }

        if ($request->has('userId')) {
            $userId = $request->userId;
            if (!empty($userId))
                $query->where('user_id', $userId);
        }

        $query->where('status', '1');

        if (!empty($search)) {
            $query->where('id', 'LIKE', "%$search%");
            // $query->orWhere('amount', 'LIKE', "%$search%");
        }

        $data = $query->paginate($pageSize, $columns, $pageName, $page);

        return response()->json($data);
    }



}