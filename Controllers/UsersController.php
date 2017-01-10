<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;

class UsersController extends Controller
{
    public function index(){
      $users = User::all();
      if($users){
        return \Response::json([
          'data' => $users
        ],200);
      }else {
        return \Response::json([
          'data' => "No Record Found"
        ],404);
      }

    }

    public function store(){
      $username = $_POST['reported_by'];
      $regId = $_POST['reg_id'];

      $user = \DB::table('usergcmregister')->where('reported_by', $username)->first();

      if($user){
        //update
        $result= \DB::table('usergcmregister')->update(
        array( 'registration_id' => $regId,
                'created_at' =>  $user->created_at,
                'updated_at' => \DB::raw('CURRENT_TIMESTAMP')));

        if($result){
          return \Response::json([
                    'success' => true,
                    'data' => array('data' => "Registration ID Updated")
                  ],200);
        }else{
          return \Response::json([
            'success' => false,
            'data' => array('error' => "Not Saved")
          ],200);
        }


      }else{
        //insert
        $data = array('registration_id' => $regId,'reported_by'=>$username,'active'=>1 );
        $result= \DB::table('usergcmregister')->insert($data);

        if($result){
          return \Response::json([
                    'success' => true,
                    'data' => array('data' => "Registration ID Added")
                  ],200);
        }else{
          return \Response::json([
            'success' => false,
            'data' => array('error' => "Not Saved")
          ],200);
        }
      }
    }
}
