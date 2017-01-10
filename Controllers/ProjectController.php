<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ProjectController extends Controller
{

    public function index($id) {
        $payload = [
            'success' => false
        ];

        $payload['image'] = $this->getImage($id);
        $payload['desc'] = $this->getDescription($id);
        $payload['success'] = true;
        return \Response::json($payload);
    }

    private function getImage($id) {
        $image_path = 'img/projects/'.$id.'.png';
        return $image_path;
    }

    private function getDescription ($id)
    {
        $text ="";
        if($id == 'iEat') {
            $text = "Online Recipe System, which displays the Recipes of different indian cuisine ";
        }

        return $text;

    }

}
