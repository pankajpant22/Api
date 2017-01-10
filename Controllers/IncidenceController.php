<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class IncidenceController extends Controller
{
  public function store(){

    $payload = [
        'success' => false,
        'data' => []
    ];
    $statusCode = 200;

    $now = new \DateTime();
    $now->setTimezone(new \DateTimeZone('America/Montreal'));
    $formatedDate = $now->format('Y-m-d H:i:s');
    $file_name = $_POST['reportedBy'].rand();

    $path = "$file_name.png";
    // $actualpath = "$base_addrs$path";
    $actualpath = \Config::get('constants.Image_Actual_Path').$path;
    $image = $_POST['image'];
    $uniqueId = rand(10000,9999999);

    $data = array(
      'incidence_id' => $uniqueId,
      'reported_name' => $_POST['reportedName'],
      'reported_incidence' => $_POST['reportedIncidence'],
      // 'image' => $_POST['image'],
      'pic' => $file_name,
      'reported_by' => $_POST['reportedBy'],
      // 'lat' => $_POST['latitude'],
      // 'long' => $_POST['longitude'],
      'moderated_by' => 'ADMIN',
      'reported_at' => $formatedDate,
      'active' => 1
    );

    /*  INSERT THE INCIDENCE */
    $result = \DB::table('incidence')->insert($data);

    if($result){
      file_put_contents($actualpath,base64_decode($image));
      $payload['data']['image'] = "Incidence Reported AND Image Saved";
    }else{
      $payload['data']['image'] = "Incidence Not Reported, Try Again !!!!!!!!";
    }

    $location_data = array(
      'reported_by' => $_POST['reportedBy'],
      'lat' => $_POST['latitude'],
      'long' => $_POST['longitude']
    );

    /*****  Update Location Of User   */
    $updateUserLocation = $this->updateUserLocation($location_data);
    if($updateUserLocation){
      $payload['success'] = true;
      $payload['data']['userLocation'] = "User Location Saved";
    }else{
      $payload['data']['userLocation'] = "User Location Not Saved, Try Again !!!!!!!!";
    }

    $radius_to_search = \Config::get('constants.RADIUS_TO_SEARCH');
    $notified = NotifyUserController::notify($data,$location_data,$radius_to_search);

    if($notified){
      $payload['success'] = true;
      $payload['data']['Notified'] = "Incidence Reported !!";
    }else {
        $payload['success'] = false;
        $payload['data']= "Incidence Not Reported !!";
    }

    return \Response::json($payload, $statusCode);
    }



  public function updatelocation()
  {
      try{
          $payload = [
              'success' => false,
              'data' => []
          ];
          $statusCode = 200;

          $location_data = array(
              'reported_by' => $_POST['username'],
              'lat' => $_POST['latitude'],
              'long' => $_POST['longitude']
          );

          /*****  Update Location Of User   */
          $updateUserLocation = $this->updateUserLocation($location_data);

          if($updateUserLocation){
              $payload['success'] = true;
              $payload['data'] = "User Location Saved";
          }else{
              $payload['data']= "User Location Not Saved, Try Again !!!!!!!!";
          }
      } catch (Exception $e){
          $statusCode = 404;
      } finally {
          return \Response::json($payload, $statusCode);
      }



  }

    public function updateUserLocation($data){

      $user = \DB::table('userlocation')->where('reported_by', $data['reported_by'])->first();
      if($user){
        //update
        $result= \DB::table('userlocation')->update(array('lat' => $data['lat'],
                'long' => $data['long'],
                'created_at' =>  $user->created_at,
                'updated_at' => \DB::raw('CURRENT_TIMESTAMP'),
                'active' => 1));
      }else{
        //insert
        $result= \DB::table('userlocation')->insert($data);
      }

      return $result;
    }

	public function updategcmlocation() {
		try {
          $payload = [
              'success' => false,
              'data' => []
          ];
          $statusCode = 200;

          $data = array(
              'reported_by' => $_POST['reported_by'],
              'reg_id' => $_POST['register_id']
	      );

          /*****  Update GCM Location Of User   */
          $user = \DB::table('usergcmregister')->where('reported_by', $data['reported_by'])->first();
		  
		  if($user){
			//update reported_by, registration_id, active, created_at, updated_at
			$result= \DB::table('usergcmregister')->update(
			array(  'registration_id' => $data['reg_id'],
					'created_at' =>  $user->created_at,
					'updated_at' => \DB::raw('CURRENT_TIMESTAMP')
					));
			$payload['success'] = true;
            $payload['data'] = "User GCM Updated";		
					
		  }else{
			//insert
			$result= \DB::table('usergcmregister')->insert(
			array(  'reported_by' => $data['reported_by'],
					'registration_id' => $data['reg_id'],
					'active' =>1,
					'created_at' =>  \DB::raw('CURRENT_TIMESTAMP')
					));
			$payload['success'] = true;
            $payload['data'] = "User GCM Added";		
		  }
      } catch (Exception $e){
          $statusCode = 404;
      } finally {
          return \Response::json($payload, $statusCode);
      }
	}

    public function getincidences()
    {

        try{
            $response = [
                'success' => false,
                'data' => [],
                'empty' => false
            ];
            $statusCode = 200;
//          $imagePath = \Config::get('constants.Image_Actual_Path');
            $imagePath = url("/")."/uploads/";
            $user = \DB::table('users')->where('name', $_POST['username'])->first();
            $results = \DB::table('notify')
                ->join('incidence', 'notify.incidence_id', '=', 'incidence.incidence_id')
                ->join('userlocation','userlocation.reported_by', '=', 'incidence.reported_by')
                ->where('notify.reported_to', $user->uid)->get();

            if(count($results)) {
                foreach ($results as $key=>$result) {
                    $payload = array();
                    $payload['incidence'] = $result->reported_incidence;
                    $payload['incidence_victim'] = $result->reported_name;
                    $payload['reported_by'] = $result->reported_by;
                    $payload['reported_at'] = $result->reported_at;
                    $payload['image'] = $imagePath.$result->pic.'.png';
                    $address = $this->getLocationAddress($result->lat,$result->long);
                    $payload['address'] = $address;
                    array_push($response['data'],$payload);
                }
            }else {
                $response['data']= "No Incidences Found";
                $response['empty'] = true;
            }
            $response['success'] = true;
        } catch (Exception $e){
            $statusCode = 404;
        } finally {
            return \Response::json($response, $statusCode);
        }

    }

    private function getLocationAddress($lat,$long)
    {
        $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$lat.','.$long;

        $response = json_decode(file_get_contents($url));
        $result = $response->results[0];
        $address = $result->formatted_address;
        return $address;
    }


}
