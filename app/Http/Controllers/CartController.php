<?php

namespace App\Http\Controllers;

use App\Models\cart;
use App\Models\cartLens;
use App\Models\cartMeasurements;
use App\Models\PrecalValues;
use App\Models\Products;
use App\Models\Thickness;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request){

        $pageSize = $request->per_page ?? 25;

        $customerId = $request->customerId;

        $columns = ['*'];

        $pageName = 'page';

        $page = $request->current_page ?? 1;

        $search = $request->searchKey ?? "";

        $query = cart::with('product')->with('customer')->orderBy('id', 'DESC')->where('customer_id',$customerId)->where('status','1');

        if(!empty($search)){
            $query->where('name', 'LIKE', "%$search%");
        }

        $data = $query->paginate($pageSize,$columns,$pageName,$page);    

        return response()->json($data);
    }

    public function store(Request $request)
    {
        try{

            $cartProductQuantities = 1;
            $productId = $request->productId;
            $customerId = $request->customerId;
            $product = Products::where('id',$productId)->where('status','1')->first();

            if($product){

                $cartProduct = cart::where('product_id', $productId)
                ->where('customer_id', $customerId)->where('status','1')->first();

                if($cartProduct){
                    $cartProductQuantities = $cartProduct->quantities + $cartProductQuantities;
                    $cartProduct->total_amount = (($cartProductQuantities) * ($product->price));
                    $cartProduct->price = $product->price;
                    $cartProduct->quantities = $cartProductQuantities;
                    $cartProduct->customer_id = $customerId;
                    $cartProduct->save();
                }else{
                    $cartProduct = new cart();
                    $cartProduct->product_id = $productId;
                    $cartProduct->customer_id = $customerId;
                    $cartProduct->quantities = $cartProductQuantities;
                    $cartProduct->total_amount = ($cartProductQuantities * $product->price);
                    $cartProduct->price = $product->price;
                    $cartProduct->status = "1";
                    $cartProduct->save();
                }

                $totalCartItems = cart::where('customer_id', $customerId)->where('status','1')->count();

                $res = [
                    'success' => true,
                    'message' => $product->name.' '.$cartProductQuantities.' time added to cart successfully.',
                    'data' => $cartProduct,
                    'totalCartItems' => $totalCartItems
                ];

            }else{
                $res = [
                    'success' => false,
                    'message' => 'product details not found.',
                    'data' => ''
                   
                ];
            }
            

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }

    }

    public function create(){
        try{

            $data['countries'] = CommonController::getCountries();

            $res = [
                'success' => true,
                'message' => 'Customer Create.',
                'data' => $data
            ];

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }
    }

    public function show($id){
        try{

            $data['cart'] = cart::with('product')
            ->with('prescription.lenspower')
            ->with('lens')
            ->with(['measurements.precalvalues','measurements.thickness'])
            ->find($id);
            $res = [
                'success' => true,
                'message' => 'Cart details.',
                'data' => $data,
            ];

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }
    }

    public function update(Request $request,$id)
    {
        try{

            $cart = cart::find($id);
            $cart->quantities = $request->quantities;
            $cart->total_amount = (($request->quantities) * ($cart->price));
            $cart->prescription_id = $request->prescriptionId;
            $cart->status = $request->status;
            $cart->save();
            $customerId = $cart->customer_id;
            $totalCartItems = cart::where('customer_id', $customerId)->where('status','1')->count();

            $res = [
                'success' => true,
                'message' => 'Cart updated successfully.',
                'totalCartItems' => $totalCartItems
            ];

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }

    }

    public function delete(Request $request)
    {
        try{
            $id = $request->id;
            $cart = cart::where('id',$id)->where('status','1')->first();
            
            if(!empty($cart)){

                $cart->status = '0';
                $cart->save();
                $res = [
                    'success' => true,
                    'message' => 'cart deleted successfully.',
                    'data' => $cart
                ];
            }else{
                $res = [
                    'success' => false,
                    'data' => 'cart details not found.',
                    'message' =>  $id
                ];
            }

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }

    }

    public function addLens(Request $request)
    {
        try{

            $lens = $request;

            $cart = cart::where('id',$lens['cartId'])->where('status','1')->first();

            if(!empty($cart)){
     
                if(empty($cart['cart_lenses_id'])){
                    
                    $cartLens = new cartLens();
                    $cartLens->cart_id = $lens['cartId'];
                    $cartLens->life_style = $lens['life_style'];
                    $cartLens->lens_recommended = $lens['lens_recommended'];
                    $cartLens->tint_type = $lens['tint_type'];
                    $cartLens->tint_value = ($lens['tint_type'] == "Colour")? $lens['colour'] : $lens['gradient'];
                    $cartLens->mirror_coating = $lens['mirror_coating'];
                
                    $cartLens->save();

                    $cart->cart_lenses_id = $cartLens->id;
                    $cart->save();

                    $res = [
                        'success' => true,
                        'message' =>'Lens Details added to cart successfully.',
                        'data' => $cartLens
                    ];

                }else{

                    $cartLens = cartLens::where('cart_id',$lens['cartId'])->where('status','1')->first();

                    $cartLens->cart_id = $lens['cartId'];
                    $cartLens->life_style = $lens['life_style'];
                    $cartLens->lens_recommended = $lens['lens_recommended'];
                    $cartLens->tint_type = $lens['tint_type'];
                    $cartLens->tint_value = ($lens['tint_type'] == "Colour")? $lens['colour'] : $lens['gradient'];
                    $cartLens->mirror_coating = $lens['mirror_coating'];
                
                    $cartLens->save();

                    $res = [
                        'success' => true,
                        'message' =>'Lens Details Updated to cart-Lenses successfully.',
                        'data' => $cartLens
                    ];
                }
               

            }else{
                $res = [
                    'success' => false,
                    'data' => 'cart details not found.',
                    'message' =>  $lens['cartId']
                ];
            }

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }

    }

    public function addMeasurements(Request $request)
    {
        try{

            $measurements = $request;

            $cart = cart::where('id',$measurements['cartId'])->where('status','1')->first();

            if(!empty($cart)){
     
                if(empty($cart['cart_measurements_id'])){
                    
                    $measurementCart = new cartMeasurements();

                    $measurementCart->cart_id = $measurements['cartId'];
                    $measurementCart->diameter = $measurements['diameter'];
                    $measurementCart->base_curve = $measurements['base_curve'];
                    $measurementCart->vertex_distance = $measurements['vertex_distance'];
                    $measurementCart->pantascopic_angle = $measurements['pantascopic_angle'];
                    $measurementCart->frame_wrap_angle = $measurements['frame_wrap_angle'];
                    $measurementCart->reading_distance = $measurements['reading_distance'];
                    $measurementCart->shape = $measurements['shapes'];
        
                    $measurementCart->lens_width = $measurements['lens_size']['lens_width'];
                    $measurementCart->bridge_distance = $measurements['lens_size']['bridge_distance'];
                    $measurementCart->lens_height = $measurements['lens_size']['lens_height'];
                    $measurementCart->temple = $measurements['lens_size']['temple'];
                    $measurementCart->total_width = $measurements['lens_size']['total_width'];
                    $measurementCart->created_at = date('Y-m-d H:i:s');
                    $measurementCart->status = 1;
                
                    $measurementCart->save();

                  

                    $precalValues = $measurements['precal_values'];

                    foreach ($precalValues as $key => $item) {
                        $precalValue = new PrecalValues();
                        $precalValue->cart_id = $measurements['cartId'];
                        $precalValue->eye_type = $key;
                        $precalValue->pd = $item['pd'];
                        $precalValue->ph = $item['ph'];
                        $precalValue->save();
                    }

                    
        
                    $thickness = $measurements['thickness'];
        
                    foreach ($thickness as $key => $item) {
                        $precalValue = new Thickness();
                        $precalValue->cart_id = $measurements['cartId'];
                        $precalValue->thickness_type = $key;
                        $precalValue->left = $item['left'];
                        $precalValue->right = $item['right'];
                        $precalValue->save();
                    }

                    $cart->cart_measurements_id = $measurementCart->id;
                    $cart->save();

                    $res = [
                        'success' => true,
                        'message' =>'Lens Details added to cart successfully.',
                        'data' => $measurementCart
                    ];

                }else{

                    $measurementCart = cartMeasurements::where('cart_id',$measurements['cartId'])->where('status','1')->first();

                    $measurementCart->diameter = $measurements['diameter'];
                    $measurementCart->base_curve = $measurements['base_curve'];
                    $measurementCart->vertex_distance = $measurements['vertex_distance'];
                    $measurementCart->pantascopic_angle = $measurements['pantascopic_angle'];
                    $measurementCart->frame_wrap_angle = $measurements['frame_wrap_angle'];
                    $measurementCart->reading_distance = $measurements['reading_distance'];
                    $measurementCart->shape = $measurements['shapes'];
        
                    $measurementCart->lens_width = $measurements['lens_size']['lens_width'];
                    $measurementCart->bridge_distance = $measurements['lens_size']['bridge_distance'];
                    $measurementCart->lens_height = $measurements['lens_size']['lens_height'];
                    $measurementCart->temple = $measurements['lens_size']['temple'];
                    $measurementCart->total_width = $measurements['lens_size']['total_width'];
                    $measurementCart->updated_at = date('Y-m-d H:i:s');
                    
                    $measurementCart->save();

                    PrecalValues::where('cart_id',$measurements['cartId'])->where('status','1')->delete();

                    $precalValues = $measurements['precal_values'];

                    foreach ($precalValues as $key => $item) {
                        $precalValue = new PrecalValues();
                        $precalValue->cart_id = $measurements['cartId'];
                        $precalValue->eye_type = $key;
                        $precalValue->pd = $item['pd'];
                        $precalValue->ph = $item['ph'];
                        $precalValue->save();
                    }
        
                    Thickness::where('cart_id',$measurements['cartId'])->where('status','1')->delete();
                    
                    $thickness = $measurements['thickness'];
        
                    foreach ($thickness as $key => $item) {
                        $precalValue = new Thickness();
                        $precalValue->cart_id = $measurements['cartId'];
                        $precalValue->thickness_type = $key;
                        $precalValue->left = $item['left'];
                        $precalValue->right = $item['right'];
                        $precalValue->save();
                    }

                    $cart->cart_measurements_id = $measurementCart->id;
                    $cart->save();

                    $res = [
                        'success' => true,
                        'message' =>'Measurements Details Updated to cart-Measurements successfully.',
                        'data' => $measurementCart
                    ];
                }
               

            }else{
                $res = [
                    'success' => false,
                    'data' => 'Measurements details not found.',
                    'message' =>  $measurements['cartId']
                ];
            }

            return response()->json($res);

        }catch(\Exception $e){
            $res = [
                'success' => false,
                'data' => 'Something went wrong.',
                'message' =>  $e->getMessage()
            ];
            return response()->json($res);
        }

    }

}
