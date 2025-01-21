<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User,Country,State,City,Query};
use App\Models\UserVerify;

use Illuminate\Support\Facades\Auth;
use Hash;
use Session;
use Mail;
use Str;
use DB;
class AuthController extends Controller
{
    public function Home()
    { 
        return view('home');
    }

    public function Login()
    {
        if(Auth::check()){
            return redirect('profile');
            }
        return view('auth.login');
    }
     
    public function Contact()
    {
        if(Auth::check()){
            return redirect('profile');
            }
       $data = Country::get(['country_id','country_name']);
        return view('auth.contact',compact('data'));
    }

    public function GetState(Request $request )
    {
        $data['states'] = State::where('country_id',$request->country_id)->get(['state_id','state_name']);
        return response()->json($data);
    }

    public function GetCity(Request $request )
    {
        $data['cities'] = City::where('state_id',$request->state_id)->get(['city_id','city_name']);
        return response()->json($data);
    }
     
    public function RefreshCaptcha()
    {
        return response()->json(['captcha' => captcha_img()]);
    }

    public function SaveContact(Request $request)
    {
      // dd ($request->all());
        $request->validate(
            [
                'name' => 'required',
                'email' => 'required|email|max:50|unique:users',
                'password' => 'required_with:confirm_passsword',
                'confirm_password' => 'required|same:password',
                'mobile' => 'required',
                'messages' => 'required',
                'dob' => 'required',
                'photo' => 'required|image|mimes:jpeg,jpg,png,gif|max:1024',
                'captcha' => 'required|captcha'
            ]
            );

            $photoname = time().'.'.$request->photo->extension();

            $request->photo->move(public_path('uploads'),$photoname);

           /* $user = new User();
            $user->name =$request->name;
            $user->email =$request->email;
            $user->password =$request->password;
            $user->mobile =$request->mobile;
            $user->messages =$request->messages;
            $user->save();*/


            $data =$request->all();
           $insert = $this->create($data,$photoname);
           $token = Str::random(64);

           UserVerify::create(
            [
                'user_id' => $insert->id,
                'token' => $token,
            ]
            );

            Mail::send('emails.activateAccountEmail', ['token' => $token], function($message) use($request){
              $message->to($request->email);
              $message->subject('Activation Email Account from Makaan');
            });
            return redirect('login')->withSuccess('Registered Successfully.Please Activate Your Account Before Login.');
    }

    public function create (array $data, string $photoname)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'password' => Hash::make($data['password']), 
            'messages' => $data['messages'],
            'dob' => $data['dob'],
            'photo' => ($photoname),
            'gender' => $data['gender'],
            'country' => $data['country'],
            'state' => $data['state'],
            'city' => $data['city'],
            'address' => $data['address'],

        ]);
    }

    public function CheckLogin(Request $request)
    {
       // dd ($request->all());
       $request->validate(
        [
            'email' => 'required|email',
            'password' => 'required',
        ]
        );
        $checkuser = $request->only('email','password');
        if(Auth::attempt($checkuser)) {
            return redirect('profile')->withSuccess('You Have Logged In Successfully.');
        }
        return redirect('login')->withSuccess('User login credentials are incorrect , please provide correct login credentials.');

    }

    public function ActivateAccount($token)
    {
       $verifyUser = UserVerify::where('token',$token)->first();
       $message = "Your details are not registered with us.";
       if(!is_null($verifyUser)){
           $user = $verifyUser->user;
           if(!$user->logged_in){
              $verifyUser->user->logged_in=1;
              $verifyUser->user->save();
       $message = "Your account is activated successfully.You can log in with your credentials.";
           }else {}
       $message = "Your account is already activated.";

       }
      return redirect('login')->with('success',$message);

    }


    public function Profile()
    {
        if(Auth::check()){
            $user = Auth::user();
        return view('profile',compact('user'));
        }
        return redirect('login')->withSuccess('Not authorized to access this page without login.');

    }

    public function Logout()
    {
      Session::flush(); 
      Auth::logout();     
      return redirect('login')->withSuccess('Logged Out Successfully.'); 

    }

    public function Edit_Profile()
    {
        if(!Auth::check()){
            return redirect('login');
        }
      else {
            $user = Auth::user();
            return view('edit_profile',compact('user'));
        }
    }
     
    public function UpdateContact(Request $request)
    {
      // dd ($request->all());
        $request->validate(
            [
                'name' => 'required',
               
            ]
            );

            $id = $request->id;
            User::where('id','=',$id)->update(
                [ 
                  'name' => $request->name,
                  'mobile' => $request->mobile,
                  'gender' => $request->gender,
                  'address' => $request->address,
                  'messages' => $request->messages,
                  'dob' => $request->dob,
                ]
                );

            return redirect('profile')->withSuccess('Your details are updated successfully.');
    } 

    public function ChangePassword()
    {
        if(!Auth::check()){
            return redirect('login');
        }
      else {
            $user = Auth::user();
            return view('change_password',compact('user'));
        }
    }

    public function UpdatePassword(Request $request)
    {
        $request->validate(
            [
                'current_password' => 'required',
                'password' => 'required_with:confirm_passsword',
            ]
            );
        
            if(!Hash::check($request->current_password,auth()->user()->password)){
            return redirect('change_password')->withSuccess('Current password did not match.');

            }


            $id = $request->id;
            User::where('id','=',$id)->update(
                [ 
                  'password' => Hash::make($request->password)
                ]
                );

            return redirect('profile')->withSuccess('Your password changed successfully.');
    } 


      public function ForgotPassword()
      {

         if(Auth::check()){
            return redirect('profile');
        }
            return view('forgotpassword');
        
         
    }
      public function ForgotPasswordSubmit(Request $request)
      {
         $request->validate([
            'email'=> 'required',
         ]);
         $token = Str::random(64);
         DB::table('resetpassword')->insert([
           'email' => $request->email,
           'token' => $token
         ]);
         Mail::send('emails.resetPasswordEmail', ['token'=>$token],function($message) use($request){
            $message->to($request->email);
            $message->subject('Reset Password Email from Makaan');
          });
          return redirect('forgotpassword')->withSuccess('Reset password email sent to your email.Please check your Inbox/Spam folder.');
      }

      public function ResetPassword($token)
      {
        if(Auth::check()) {
            return redirect('profile');
         }
         return view('resetpassword',['token'=>$token]);
      }

      public function ResetPasswordSubmit(Request $request)
      {
        
       // dd($request->all());
        $request->validate([
            'email'=> 'required',
            'password' =>  'required|same:confirm_password',
            'confirm_password'=> 'required'
         ]);
        $resetPasswordCheck = DB::table('resetpassword')
                            ->where([
                                'email' => $request->email,
                                'token' => $request->token
                            ])->first();
         if(!$resetPasswordCheck) {
          return redirect('resetpassword/'.$request->token)->withSuccess('Reset password link is expired.');
         }      
         $userPasswordUpdate = User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
         ]);
         DB::table('resetpassword')->where(['email'=>$request->email])->delete();
         return redirect('login')->withSuccess('Your password is reset successfully.Please login with new password.');     
        }

    public function Query()
    {
        return view('query');
    }
    public function SaveQuery(Request $request)
   {
    // dd ($request->all());
    $request->validate(
        [
            'name' => 'required',
            'email' => 'required',
            'mobile' => 'required',
            'messages' => 'required',
        ]
        );
        $customer = new Query();
            $customer->name =$request->name;
            $customer->email =$request->email;
            $customer->mobile =$request->mobile;
            $customer->messages =$request->messages;
            $customer->save();
         return redirect('query')->withSuccess('Your query is submitted successfully.');     

    } 
    
}
 