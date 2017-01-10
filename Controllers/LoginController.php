<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Input;

class LoginController extends Controller
{
    /*
    *  The Function is used for checking the login action
    *  Parameter passed email and Password
    *
    */

    public function index() {

      try{
            $payload = [
                'success' => false,
                'data' => []
            ];
            $statusCode = 200;

            $email = $_POST['email'];
            $password = $_POST['password'];
            $matchThese = ['email' => $email, 'active' => 1];
            $results = User::where($matchThese)->get();

            if (!$results->isEmpty()) {
              $storedPassword = $results->first()->toArray()['password'];
              $matchPassword = \Hash::check($password, $storedPassword);
              if($matchPassword) {
                $result = $results->toArray();
                foreach ($result as $record) {
                      $payload['data']["uid"] = $record["uid"];
                      $payload['data']["name"] = $record["name"];
                      $payload['data']["email"] = $record["email"];
                      $payload['data']["created_at"] = $record["created_at"];
                }
                $payload['success'] = true;
              } else {
                $payload['data'] = "wrong Password";
              }
            } else {
              $payload['data'] = "No Record Found";
            }

        } catch (Exception $e){
            $statusCode = 404;
        } finally {
            return \Response::json($payload, $statusCode);
        }


      // var_dump ($results->count());
      // if (!$result->isEmpty()) { }
      // if ($result->count()) { }
      // if (count($result)) { }
      //
      // print_r($results);
      // $data = array('data' =>$name,'password' => $password);
      // return json_encode($data);
    }


    /*
    *  The Function is used for creating user
    *  Parameter passed email,name and Password
    *
    */

    public function store(){

            try {

            $payload = [
                'success' => false,
                'data' => []
            ];
            $statusCode = 200;

            if (!isset($_POST['name']) && !isset($_POST['email']) && !isset($_POST['password'])) {
                $payload['data'] = "Required parameters (name, email or password) is missing!";
            } else {
                $email = $_POST['email'];
                $username = $_POST['name'];
                $password = $_POST['password'];
                $matchThese = ['email' => $email, 'active' => 1];
                $results = User::where($matchThese)->get();
                if (!$results->isEmpty()) {
                    $payload['data'] = "User already exists";
                }else {
                    $uuid = rand(10000,9999999);
                    \DB::table('users')->insert([
                        'uid'  => $uuid,
                        'name' => $username,
                        'email' => $email,
                        'password' => bcrypt($password),
                        'active' => 1,
                    ]);

                    $matchThese = ['email' => $email, 'active' => 1];
                    $user = User::where($matchThese)->get();
                    $result = $user->toArray();
                    foreach ($result as $record) {
                        $payload['data']["uid"] = $record["uid"];
                        $payload['data']["name"] = $record["name"];
                        $payload['data']["email"] = $record["email"];
                        $payload['data']["created_at"] = $record["created_at"];
                    }
                    $payload['success'] = true;
                }
            }
        } catch (Exception $e){
            $statusCode = 404;
        } finally {
            return \Response::json($payload, $statusCode);
        }
    }
}
