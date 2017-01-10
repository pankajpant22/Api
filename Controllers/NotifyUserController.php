<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class NotifyUserController extends Controller
{
    public static function notify ($data,$location,$radius = 500){

      $payload = [
            'success' => true,
            'data' => []
      ];

      $usersFound = NotifyUserController::getUsers($data,$location,$radius);

      
      if($usersFound == null){
          $payload['data']['usersFound'] = "No Users Found !!!";
          return \Response::json($payload);
      }

      $userNotified = NotifyUserController::pushNotifyUsers($data, $usersFound);

      /*
        // NotifyUserController::setInterval('pushNotifyUsers()', 3600000, 2); // Invoke every second, up to 100 times, milliseconds used 1000 for 1 sec
      $interval = 0; // 3600000
      $times = 1;
      $seconds = $interval * 1000;
      if($times > 0){
        $i = 0;
        while($i < $times){
            // call_user_func($func);
            $userNotified = NotifyUserController::pushNotifyUsers($data, $usersFound);
            $i++;
            usleep( $seconds );
        }
        $payload['data']['usersNotified'] = $userNotified;
      }
      */

      if($userNotified)
          return true;
      else
          return false;
    }

    private static function getUsers ($data,$location,$radius){
      $lat = $location['lat']; // latitude of centre of bounding circle in degrees
      $long = $location['long']; // longitude of centre of bounding circle in degrees
      // $rad = $radius; // radius of bounding circle in kilometers

      // $R = 6371;  // earth's mean radius, km
      //
      // // first-cut bounding box (in degrees)
      // $maxLat = $lat + rad2deg($rad/$R);
      // $minLat = $lat - rad2deg($rad/$R);
      // // compensate for degrees longitude getting smaller with increasing latitude
      // $maxLon = $lon + rad2deg($rad/$R/cos(deg2rad($lat)));
      // $minLon = $lon - rad2deg($rad/$R/cos(deg2rad($lat)));


      $results = \DB::select( \DB::raw("
            SELECT *,
            ACOS( SIN( RADIANS( `lat` ) ) * SIN( RADIANS( '$lat' ) ) + COS( RADIANS( `lat` ) )
            * COS( RADIANS('$lat' )) * COS( RADIANS( `long` ) - RADIANS( '$long' )) ) * 6380 AS `distance`
            FROM `userlocation`
            WHERE
            ACOS( SIN( RADIANS( `lat` ) ) * SIN( RADIANS( '$lat' ) ) + COS( RADIANS( `lat` ) )
            * COS( RADIANS( '$lat' )) * COS( RADIANS( `long` ) - RADIANS( '$long' )) ) * 6380 < '$radius'
            ORDER BY `distance`
            "));

      return (count($results)) ? json_decode(json_encode((array) $results), true) : null;
    }


    private static function setInterval($func = null, $interval = 0, $times = 0){

          if( ($func == null) || (!function_exists($func)) ){
            throw new Exception('We need a valid function.');
          }
          /*
          usleep delays execution by the given number of microseconds.

          JavaScript setInterval uses milliseconds. microsecond = one

          millionth of a second. millisecond = 1/1000 of a second.

          Multiplying $interval by 1000 to mimic JS.
          */
            $seconds = $interval * 1000;

            /*

            If $times > 0, we will execute the number of times specified.

            Otherwise, we will execute until the client aborts the script.

            */

            if($times > 0){
              $i = 0;
              while($i < $times){
                  call_user_func($func);
                  $i++;
                  usleep( $seconds );
              }
            } else {
              while(true){
                  call_user_func($func); // Call the function you've defined.
                  usleep( $seconds );
              }

            }

      }

      public static function pushNotifyUsers($data,$usersFound) {

          /*
          $prefix = '';
          $userList = '(';
          foreach ($usersFound as $key => $value)
          {
              $userList .= $prefix . '"' . $value['reported_by'] . '"';
              $prefix = ',';
          }
          $userList .=')';
          */

          $users = array();
          foreach ($usersFound as $key => $value)
          {
              $users[]=$value['reported_by'];
          }

          
          // save to database
          $results = \DB::table('users')
              ->join('usergcmregister', 'usergcmregister.reported_by', '=', 'users.name')
              ->whereIn('usergcmregister.reported_by', $users)->get();
          $resultArray = json_decode(json_encode((array) $results), true);

          $incidence_id = $data['incidence_id'];
          $reportedUser = \DB::table('users')->where('name', $data['reported_by'])->first();
          $reported_by = $reportedUser->uid;
          $dataToSave = array();
          foreach ($resultArray as $key => $value){
              $testarray = array(
                  'incidence_id' => $incidence_id,
                    'reported_by' => $reported_by,
                    'reported_to' => $value['uid'],
                    'active' => 1
              );
              array_push($dataToSave,$testarray);
          }

          $saveData = \DB::table('notify')->insert($dataToSave);

          // send message
          $registration_ids = array();
          foreach ($resultArray as $key => $value)
          {
              array_push($registration_ids, $value['registration_id']);
          }

          $message = array("Notice" => "New Incidence Reported !!!!!");

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
          return true;
//          return \Redirect::to('testgcm')->with('message', 'Message Sent');
      }
}
