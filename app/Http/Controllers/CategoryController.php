<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{

    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }
    public function index(){
        $categories = Category::all();
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'categories' => $categories
        ]);
    }

    public function show($id){
        $category = Category::find($id);
        if (is_object($category)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $category
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'la categoria no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        //recoger datos x post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //validar datos
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado la categoria',
                    'errors' => $validate->errors()
                ];
            }else{
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Categoria guardada con exito'
                ];
            }
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No has enviado ningun dato'
            ];
        }
        return response()->json($data,$data['code']);
    }

    public function update($id, Request $request){
        //recoger parametros 
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        if (!empty($params_array)) {
            //validar datos
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);
            //quitar parametros que no se actualizan
            unset($params_array['id']);
            unset($params_array['created_at']);
            //actualizar el registro
            $category = Category::where('id', $id)->update($params_array);
            $msg = $category ? "Categoria actualizada": "No se pudo actualizar";
            $data = [
                'code' => 200,
                'status' => 'success',
                'message' => $msg,
                'category' => $params_array
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No has enviado ningun dato'
            ];
        }
        return response()->json($data,$data['code']);
    }
}
