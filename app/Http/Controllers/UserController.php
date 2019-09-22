<?php

namespace App\Http\Controllers;

use App\Helpers\JWTAuth;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function register(Request $request){
        //Recoger datos de usuario por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        //Limpiar datos
        $params_array = array_map('trim', $params_array);
        if (!empty($params) && !empty($params_array)) {
            //Validar datos
            $validate = \Validator::make($params_array, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users',
                'password' => 'required'
            ]);
            //Si la validacion falla
            if ($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'el usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            }else{
                //Cifrar contraseña
                $pwd = hash('sha256', $params->password);
                //Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';
                $user->save(); 
                //Validación correcta
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'el usuario se ha creado'
                );
            }
        }else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'los datos enviados son incorrectos',
            );
        }
        return response()->json($data, $data['code']);
    }

    public function login(Request $request){
        $jwtAuth = new JWTAuth();

        //Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        //validar los datos
        $validate = \Validator::make($params_array, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        //Si la validacion falla
        if ($validate->fails()) {
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'el usuario no se ha podido logear',
                'errors' => $validate->errors()
            );
        }else{
            //Cifrar password
            $pwd = hash('sha256', $params->password);
            //Devolver token
            $signup = $jwtAuth->signup($params->email, $pwd);
            if (!empty($params->getToken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }
        return response()->json($signup, 200);
    }

    public function update(Request $request){
        $token = $request->header('Authorization');
        $jwtAuth = new JWTAuth();
        $checktoken = $jwtAuth->checkToken($token);

        //Recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        //Comprobar si está identificado
        if ($checktoken && !empty($params_array)) {            
            //Traer usuario identificado
            $user = $jwtAuth->checkToken($token, true);
            //Validar datos
            $validate = \Validator::make($params_array, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users,'.$user->sub
            ]);
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);
            $user_update = User::where('id', $user->sub)->update(
                $params_array
            );
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        }
        else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'EL usuario no está identificado'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request){
        //Recoger datos de la peticion
        $image = $request->file('file0');

        //Validar imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        //Si falla la validacón
        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'error al subir la imagen',
                'errors' => $validate->errors()
            );
        }else{
            //Subir imagen  
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));
            $data = array(
                'image' => $image_name,
                'code' => 200,
                'status' => 'success'
            );
        }
        return response($data, $data['code']);
    }

    public function getImage($filename){
        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file,200);
        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );
        }
        return response($data, $data['code']);

    }

    public function detail($id){
        $user = User::find($id);
        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'el usuario no existe'
            );
        }
        return response()->json($data, $data['code']);
    }
}
