<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class GcmController extends Controller
{
  public function index(){
    $gcmusers = \DB::select('SELECT `id`,`reported_by`,`registration_id` FROM `usergcmregister`');
    $count = count($gcmusers);
    $apikey = \Config::get('constants.GOOGLE_API_KEY');

    return view('gcm',compact('gcmusers','count'));
  }

  public function sendMessage(){

    $registration_ids = array();
    array_push($registration_ids, \Request::get('reg_id'));

    $message = array("Notice" => \Request::get('message'));

    $userdata = array(
            'registration_ids'  => $registration_ids,
            'data'  => $message
        );

    $apikey = \Config::get('constants.GOOGLE_API_KEY');
    // Set POST variables
    $url = 'https://android.googleapis.com/gcm/send';

    $headers = array(
                'Authorization: key=' . $apikey,
                'Content-Type: application/json'
            );
            // Open connection
                $ch = curl_init();

                // Set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);

                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Disabling SSL Certificate support temporarly
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userdata));


                // Execute post
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($result === FALSE) {
                    die('Curl failed: ' . curl_error($ch));
                }

                // Close connection
                curl_close($ch);
                echo $result;
                return \Redirect::to('testgcm')->with('message', 'Message Sent');
  }

}
