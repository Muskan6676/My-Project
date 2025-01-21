<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\AdminVerify;
use Hash;
use App\Models\Place;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Session;
use Mail;
use Str;

class AdminController extends Controller
{
    public function Logins()
    {
        return view('admin.logins');
    }
    public function Registers()
    {
        return view('admin.registers');
    }
    public function SaveRegisters(Request $request)
    {
         //dd($request->all());
             $request->validate(
            [
           'first_name'  => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:8|same:repeat_password',
            'repeat_password' => 'required'
            
        ]); 

        $data = $request->all();
        $insert = $this->create($data);
        $token = Str::random(64);

        AdminVerify::create(
            [
                'admin_id' => $insert->id,
                'token' => $token, 
            ]
            );

            Mail::send('admin.activateAdminAccountEmail', ['token' => $token], function($message) use($request){
                $message->to($request->email);
                $message->subject('Activation Email Account from Makaan');
              });
        return redirect('logins')->withSuccess('Registered Successfully.Please Activate Your Account Before Login.');
    
    }
        public function create(array $data)
        {
            return Admin::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'repeat_password' => $data['repeat_password'],
            ]);

        }

    public function CheckLogin(Request $request)
    {
      //dd($request->all());
       $request->validate(
        [
        'email' => 'required|email',
        'password' => 'required',
        
    ]); 
    $checkadmin = $request->only('email','password');
    if(Auth::attempt($checkadmin)) {
    return redirect('dashboards')->withSuccess('LoggedIn  Successfully.');
    }
    return redirect('logins')->withSuccess('Admin login credentials are incorrect.Please provide correct login credentials to login');
    }

    public function ActivateAccount($token)
    {
       $verifyadmin = AdminVerify::where('token',$token)->first();
       $message = "Your details are not registered with us.";
       if(!is_null($verifyadmin)){
           $admin = $verifyadmin->admin;

           if(!$admin->is_email_verified){
              $verifyadmin->admin->is_email_verified=1;
              $verifyadmin->admin->save();
       $message = "Your account is activated successfully.You can log in with your credentials.";
           }else {
       $message = "Your account is already activated.";
           }
          }  else{
            $message ="Unable to activate your account.";
       }
      return redirect('logins')->with('success',$message);
     }
    
     public function Logouts()
     {
       Session::flush();
       Auth::logouts();     
       return redirect('logins'); 
 
     }


    public function Dashboards()
    {
        return view('admin.dashboards');
    }
    public function Users()
    {
        $data = User::get();
        return view('admin.users',compact('data'));
    }
    public function EditUsers($id)
    {
        $data = User::where('id',$id)->first();
        return view('admin.editusers',compact('data'));
    }
    public function UpdateUsers(Request $request)
    {
       // dd ($request->all());
       $request->validate(
        [
            'name' => 'required',
            'email' => 'required|email|max:50',
            'mobile' => 'required',
            'messages' => 'required',
            'dob' => 'required',
            'gender' => 'required',
            'address' => 'required',
            'photo' => 'required|image|mimes:jpeg,jpg,png,gif|max:1024',
        ]
        );

        $id = $request->id;
        $name = $request->name ;
        $email = $request->email ;
        $mobile = $request->mobile ;
        $messages = $request->messages ;
        $address = $request->address;
        $dob = $request->dob ;
        $gender = $request->gender;
        $photo = $request->photo ;


        User::where('id',$id)->update([
             'name' => $name,
             'email' => $email,
             'mobile' => $mobile,
             'messages' => $messages,
             'dob' => $dob,
             'gender' => $gender,
             'photo' => $photo,
             'address' =>$address,

        ]);
        return redirect('editusers/'.$id)->withSuccess('User details updated successfully');


    }
    public function AdminChangePassword($id)
    {
        $data = Admin::where('id',$id)->first();
        return view('admin.changepassword',compact('data'));
    }

    public function UpdatePassword(Request $request)
    {
        $request->validate(
            [
                'password' => 'required_with:repeat_passsword',
                'current_password' => 'required',
               
            ]
            );
        
            if(!Hash::check($request->current_password,auth()->admin()->password)){
            return redirect('adminchangepassword/'.$id)->withSuccess('Current password did not match.');

            }


            $id = $request->id;
            User::where('id','=',$id)->update(
                [ 
                  'password' => Hash::make($request->password)
                ]
                );

            return redirect('editusers/'.$id)->withSuccess('Your password changed successfully.');
    } 
    public function Places(){
        $data = Place::get();
        return view('admin.places',compact('data'));
    }

    public function AddPlaces(){
        return view('admin.add_places');
    }
    public function SavePlace(Request $request){

        $photoName = time().'.'.$request->photo->extension();
        $request->photo->move(public_path('uploads/places'),$photoName);

         Place::create([
            'place_type' => $request->place_type,
            'name' => $request->name,
            'description' => $request->description, 
            'photo' => $photoName,
            'price' => $request->price,
            'location' => $request->location,
        ]);
        return redirect('add_places')->withSuccess('Place added Successfully.');

    }

    public function EditPlace($id)
    {
        $data = Place::where('id',$id)->first();
        return view('admin.edit_place',compact('data'));
    }

    public function UpdatePlace(Request $request)
    {
       // dd ($request->all());
       $request->validate(
        [
            'place_type' => 'required',
            'name' => 'required',
            'description' => 'required',
            'photo' => 'required',
            'price' => 'required',
            'location' => 'required',
        ]
        );

        $id = $request->id;
        $place_type = $request->place_type ;
        $name = $request->name ;
        $description = $request->description ;
        $photo = $request->photo ;
        $price = $request->price;
        $location = $request->location ;

        $photoName = time().'.'.$request->photo->extension();
        $request->photo->move(public_path('uploads/places'),$photoName);
        
        Place::where('id',$id)->update([
             'place_type' => $place_type,
             'name' => $name,
             'description' => $description,
             'photo' => $photoName,
             'price' => $price,
             'location' => $location,

        ]);
        return redirect('edit_place/'.$id)->withSuccess('Place details updated successfully');


    }

    public function Room()
    {
        return view('Rent.room');
    }
    public function DeleteUsers($id)
    {
        $data = User::where('id','=',$id)->delete();
        return redirect()->back()->withSuccess('User Deleted Successfully.');
        
    }
    public function AddUsers()
    {
        return view('admin.add_users');
    }
public function SaveUsers(Request $request)
{
    //dd($request->all());
    $request->validate(['name'=> 'required']);

    $user = new User();
    $user->name = $request->name;
    $user->email = $request->email;
    $user->mobile = $request->mobile;
    $user->password = $request->password;
    $user->photo = $request->photo;
    $user->dob = $request->dob;
    $user->messages = $request->messages;
    $user->gender = $request->gender;
    $user->address = $request->address;
    $user->save();
    return redirect()->back()->with('success','User added successfully.');  

}
}

