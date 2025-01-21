<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use App\Models\Place;
use DB;

class PlaceController extends Controller
{
    public function index() {
        $place = Place::all();
        return view('Property.properties',compact('place'));                                           
    }
    public function EditPlace($id)
    {
        $data = Place::where('id',$id)->first();
        return view('admin.edit_place',compact('data'));
    }
    public function Cart()
    {
        if(!Auth::check()){
            return redirect('login');
            } else{
                $user = Auth::user();
        return view('cart',compact('user'));
            }
    }
    public function AddToCart($id)
    {
       $place = Place::findOrFail($id);
       $cart = session()->get('cart',[]);
       if(isset($cart[$id])) {
           $cart[$id]['quantity']++;
       } else {
           $cart[$id] = [
            'place_type' =>$place->place_type,
            'quantity' => 1,
            'name' => $place->name,
            'description' => $place->description,
            'photo' => $place->photo,
            'price' => $place->price,
            'seller' => $place->location,
        ];
       }
       session()->put('cart',$cart);
       return redirect('cart')->withSuccess('Place added in cart successfully.');
    }

    public function DeleteCartPlace(Request $request)
    {
        if($request->id)
        {
            $cart = session()->get('cart');
            if(isset($cart[$request->id]))
            {
                unset($cart[$request->id]);
                session()->put('cart',$cart);
            }
            session()->flash('success','Place is deleted from your Bookings Page.');
        }
    }


    public function Book()
    {
        if(!Auth::check()) {
            return redirect('login');
        }
        $cart = session()->get('cart',[]);
        $user = Auth::user();

        $total=0;
        foreach ($cart as $id => $place)
        {
            $total = $total + $place['price']*$place['quantity'];
        }
        $book_id = DB::table('book')->insertGetId([
            'name' => $user->name,
            'email' => $user->email,
            'address' => $user->address,
            'total_amount' => $total
        ]);
        foreach ($cart as $id => $place)
        {
            DB::table('book_details')->insert([
                'book_id' => $book_id,
                'place_name' => $place['name'],
                'price' => $place['price'],
                'quantity' => $place['quantity'],
                'sub_amount' => $place['price']*$place['quantity'],
            ]);
        }
       return redirect('home');

    }


    

    public function SaveOrder(Request $request)
    {
        //dd($request->all());
        $random = rand(00000000,99999999);
        if(!Auth::check()) {
            return redirect('login');
        }
        $cart = session()->get('cart',[]);
        $user = Auth::user();

        $total=0;
        foreach ($cart as $id => $place)
        {
            $total = $total + $place['price']*$place['quantity'];
        }
        $book_id = DB::table('book')->insertGetId([
            'name' => $user->name,
            'email' => $user->email,
            'address' => $user->address,
            'total_amount' => $total,
            'payment_id' =>$random,
            //'payment_status' => 'pending',
           // 'payment_date' => '',
           //'payment_card_details' => '',
        ]);
        foreach ($cart as $id => $place)
        {
            DB::table('book_details')->insert([
                'book_id' => $book_id,
                'place_name' => $place['name'],
                'price' => $place['price'],
                'quantity' => $place['quantity'],
                'sub_amount' => $place['price']*$place['quantity'],
            ]);
        }
       return redirect('payment/'.$random);

    }
   
    public function Payment($payment_id)
    {
      // echo $payment_id; exit;
      if(!Auth::check()) {
        return redirect('login');
    }
       $user = Auth::user();
       $book_details = DB::table('book')->where('payment_id','=',$payment_id)->first();
      return view('payment',compact('user','book_details','payment_id'));
    }

    public function PaymentSave(Request $request)
   {
        //dd($request->all());
       
   }

    public function indexs()
    {
        $categories = Place::all(); // Assuming you have a Category model
        return view('Property.indexs', compact('categories'));
    }

    public function filter(Request $request)
    {
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $category = $request->input('place_type');

        $products = Place::query();

        if ($minPrice !== null) {
            $products->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $products->where('price', '<=', $maxPrice);
        }

        if ($category !== null) {
            $products->whereHas('place_type', function ($query) use ($category) {
                $query->where('name', $category);
            });
        }

        $filteredProducts = $products->get();

        return view('Property.indexs', compact('filteredProducts'));
    }
    public function PlaceDetails()
    {
        $data = Place::get();
        return view('Property.place-details',compact('data'));
    }
   
}