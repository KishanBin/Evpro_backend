<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\DB;

class MailController extends Controller
{
   function sendOtp(Request $request){
     
      $request->validate([
        'email' => 'required|email',
    ]);

    // Retrieve the email from the request
    $email = $request->input('email');
    $to = $email;
    $otp = rand(100000, 999999);
    $subject = "Email Verification";
     
    $emailExists = DB::table('email_otp')->where('email', $email)->exists();
     
     if($emailExists){
       // Email exists, update the OTP and updated_at timestamp
       DB::table('email_otp')->where('email', $email)->update([
            'otp' => $otp,
            'updated_at' => now(),
        ]);
     }else{
     
       // Email doesn't exist, insert a new record with the email and OTP
        DB::table('email_otp')->insert([
            'email' => $email,
            'otp' => $otp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
       
     }
     
    try {
        Mail::to($to)->send(new VerificationEmail($otp, $subject));
        return response()->json(['status' => true, 'message' => 'OTP sent successfully']);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Failed to send OTP']);
    }
     
   }
  
    function otpVerification(Request $request){
      
      $request->validate([
        'email' => 'required|email',
        'otp' => 'required|numeric',
    ]);

    // Retrieve the email and OTP from the request
    $email = $request->input('email');
    $otp = $request->input('otp');

    // Check if the email exists in the email_otp table
    $record = DB::table('email_otp')->where('email', $email)->first();

    if ($record) {
        // Verify if the OTP matches
        if ($record->otp == $otp) {
            return response()->json(['status' => true, 'message' => 'OTP verified']);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid OTP']);
        }
    } else {
        return response()->json(['status' => false, 'message' => 'Email not found']);
    }
      
    }
}
