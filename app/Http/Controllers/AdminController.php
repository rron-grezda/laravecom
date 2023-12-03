<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use constGuards;
use constDefaults;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    public function loginHandler(Request $request){
        $fieldType = filter_var($request->login_id, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        if($fieldType == 'email'){
            $request->validate([
                'login_id'=>'required|email|exists:admins,email',
                'password'=>'required|min:5|max:45'
            ],[
                'login_id.required'=>'Email or Username is required',
                'login_id.email'=>'Invalid email address',
                'login_id.exists'=>'Email does not exist in system',
                'password.required'=>'Password is required'
            ]);
        }else{
            $request->validate([
                'login_id'=>'required|exists:admins,username',
                'password'=>'required|min:5|max:45'
            ],[
                'login_id.required'=>'Email or Username is required',
                'login_id.exists'=>'Username does not exist in system',
                'password.required'=>'Password is required'
            ]);
        }

        $creds = array(
            $fieldType =>$request->login_id,
            'password'=>$request->password
        );

        if( Auth::guard('admin')->attempt($creds) ){
            return redirect()->route('admin.home');
        }else{
            session()->flash('fail', 'Incorrect credentials');
            return redirect()->route('admin.login');
        }
    }

    public function logoutHandler(Request $request){
        Auth::guard('admin')->logout();
        session()->flash('fail', 'You are logged out!');
        return redirect()->route('admin.login');
    }

    public function sendPasswordResetLink(Request $request){
        $request->validate([
            'email'=>'required|email|exists:admins,email'
        ],[
            'email.required'=>'The :attribute is required',
            'email.email'=>'Invalid email address',
            'email.exists'=>'The :attribute does not exists in system'
        ]);
        
        //Get admin details
        $admin = Admin::where('email',$request->email)->first();

        //Generate token
        $token = base64_encode(Str::random(64));

        //Check if there is an existing reset password token
        $oldToken = DB::table('password_resets')
                      ->where(['email'=>$request->email, 'guard'=>constGuards::ADMIN])
                      ->first();
                      
        if($oldToken) { 
            //Update token if there is an existing token
            DB::table('password_resets')
              ->where(['email'=>$request->email, 'guard'=>constGuards::ADMIN])
              ->update([
                'token'=>$token,
                'created_at'=>Carbon::now()
              ]);
        }else{
            //Add new token
            DB::table('password_resets')->insert([
                'email'=>$request->email,
                'guard'=>constGuards::ADMIN,
                'token'=>$token,
                'created_at'=>Carbon::now()
            ]);
        }

        $actionLink = route('admin.reset-password', ['token'=>$token, 'email'=>$request->email]);
        $data = array(
            'actionLink'=>$actionLink,
            'admin'=>$admin
        );

        $mail_body = view('email-templates.admin-forgot-email-template', $data)->render();

        $mailConfig = array(
            'mail_from_email'=>env('EMAIL_FROM_ADDRESS'),
            'mail_from_name'=>env('EMAIL_FROM_NAME'),
            'mail_recipient_email'=>$admin->email,
            'mail_recipient_name'=>$admin->name,
            'mail_subject'=>'Reset password',
            'mail_body'=>$mail_body
        );

        if( $mailConfig ) {
            session()->flash('success','We have emailed your password reset link.');
            return redirect()->route('admin.forgot-password');
        }else{
            session()->flash('fail','Something went wrong!');
            return redirect()->route('admin.forgot-password');
        }
    }

    public function resetPassword(Request $request, $token = null) {
        $check_token = DB::table('password_resets')
                         ->where(['token'=>$token, 'guard'=>constGuards::ADMIN])
                         ->first();   
        if($check_token) {
            $diffMins = Carbon::createFromFormat('Y-m-d H:i:s', $check_token->created_at)->diffInMinutes(CarbonLLnow());

            if($diffMins > constDefaults::tokenExpiredInMinutes){
                //If token expired
                session()->flash('fail','Token expired, request another reset password link.');
                return redirect()->route('admin.forgot-password', ['token'=>$token]);
            }else {
                return view('back.pages.admin.auth.reset-password')->with(['token'=>$token]);
            }
        }else {
            session()->flash('fail','Invalid token!, request another reset password link');
            return redirect()->route('admin.forgot-password', ['token'=>$token]);
        }
    }

    public function resetPasswordHandler(Request $request){
        $request->validate([
            'new_password'=>'required|min:5|max:45|required_with:new_password_confirmation|same:new_password_confirmation',
            'new_password_confirmation'=>'required'
        ]);
        $token = DB::table('password_resets')
                    ->where(['token'=>$request->token, 'guard'=>constGuards::ADMIN])
                    ->first();

        //Get admin's details
        $admin =  Admin::where('email',$token->email)->first();

        //Update admin's password
        Admin::where('email'->$admin->email)->update([
              'password'=>Hash::make($request->new_password)
        ]);

        //Delete token
        DB::table('password_resets')->where([
            'email'=>$admin->email,
            'token'=>$request->token,
            'guard'=>constGuards::ADMIN
        ])->delete();
    }

    public function profileView(Request $request) {
        $admin = null;
        if(Auth::guard('admin')->check()) {
            $admin = Admin::findOrFail(auth()->id());
        }
        return view('back.pages.admin.profile', compact('admin'));
    }
}
