<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;

class Authentication extends Controller
{
  public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userType' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8', // Ensure password confirmation
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()->first()]); // Return validation errors
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Hash the password before saving
            ]);
          
            $user->user_type = $request->userType; // Correcting the assignment
            $user->save(); // Save the updated user instance
           
            return response()->json(["status" => true, "message" => "User Successfully Register"]);
        }
    }

    public function login(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to log the user in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Authentication passed, get the user
            $user = Auth::user();
            $token = Str::random(60);
            $user->login_token = $token;
            $user->save(); // don't remove save code is correct
          
           $imageUrl = 'http://srv710339.hstgr.cloud/images/1738525088.jpg';
            if ($user->image) {
               $imageUrl = url('http://srv710339.hstgr.cloud/images/'.$user->image); // Construct the URL to the image
             }


            // Optionally, you can return a token or user data
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'data' => [
            'id' => $user->id,
            'user_type' =>$user->user_type,      
            'name' => $user->name,
            'email' => $user->email,
            'image' => $imageUrl, // Include the image URL in the response
        ]
            ], 200);
        }

        // Authentication failed
        return response()->json([
            'status' => false,
            'message' => 'Invalid email or password',
        ]);
    }

    public function checkLoginToken(Request $request)
    { // Validate the request
        $request->validate(['login_token' => 'nullable|string',]);

        $user = User::where('login_token', $request->input('login_token'))->first();

        if ($user) { // Token is valid

            return response()->json(['status' => true, 'message' => 'Valid login token',], 200);
        } else { // Token is invalid
            return response()->json(['status' => false, 'message' => 'Invalid login token',], 200);
        }
    }
  
     public function profileUpdate(Request $request)
    {
       
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'userId' => 'required', 
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|string|email',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // Check if validation fails
    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
    } else {
        // Update the user's name and email
        $user = User::find($request->userId);
      
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found']);
        }
      
      if($request->hasFile('name') || $request->hasFile('email')){
        $user->name = $request->name;
        $user->email = $request->email;
      }

        // Handle the profile image upload
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($user->image && file_exists(public_path('images') . '/' . $user->image)) {
                unlink(public_path('images') . '/' . $user->image);
            }

            // Save the new image
            $imageName = time().'.'.$request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $user->image = $imageName; // Assuming the `users` table has an `image` column
        }

        // Save the user
        $user->save();
      
        $imageUrl = 'http://srv710339.hstgr.cloud/images/1738525088.jpg';
            if ($user->image) {
               $imageUrl = url('http://srv710339.hstgr.cloud/images/'.$user->image); // Construct the URL to the image
             }

        return response()->json(['status' => true, 'message' => 'Profile updated successfully.',
  'data' => [
            'id' => $user->id,
            'user_type' =>$user->user_type,      
            'name' => $user->name,
            'email' => $user->email,
            'image' => $imageUrl, // Include the image URL in the response
              ] 
                                ]);
    }
       
    }

    public function sendOtp(Request $request)
    {
        // Validate the request
        $request->validate(['email' => 'required|email']);

        // Generate a random OTP
        $otp = rand(100000, 999999);

        // Send the OTP email
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(['message' => 'OTP sent successfully!']);
    }
}
