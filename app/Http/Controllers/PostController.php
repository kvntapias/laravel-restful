<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\Helpers\JWTAuth;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => 
                                        [   'index', 
                                            'show', 
                                            'getImage',
                                            'getPostsByCategory',
                                            'getPostsByUser'
                                        ]
                                    ]
        );
    }

    public function index(){
        $posts = Post::all()->load('category');
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ],200);
    }

    public function show($id){
        $post = Post::find($id)->load('category');
        if (is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'posts' => $post
            ];
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'la entrada no existe'
            ];
        }
        return response()->json($data,$data['code']);
    }

    public function store(Request $request){
        //recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        if (!empty($params)) {
            //conseguir user identificado
            $user =$this->getIdentity($request);
            //validar datos
            $validate = \Validator::make($params_array, [
                'title' => 'required|max:255',
                'content' => 'required|max:500',
                'category_id' => 'required|exists:categories,id',
                'image' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Verifique los campos',
                    'errors' => $validate->errors()  
                ];
            }else{
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                //guardar el post
                $post->save();
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Post guardado con exito'  
                ];
            }
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Datos incorrectos'  
            ];
        }
        return response()->json($data,$data['code']);
    }

    public function update($id, Request $request){
        //recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        $data = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos incorrectos'
        ];
        if (!empty($params_array)) {
            //validar datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data['errors'] = $validate->errors();
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos incorrectos'
                ];
                return response()->json($data, $data['code']);
            }
            //eliminar datos no actualizables
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            $user  =  $this->getIdentity($request);
            //buscar registro
            $post = Post::where('id',$id)
                    ->where('user_id', $user->sub)
                    ->first();
            if (!empty($post) && is_object($post)) {
                //Actualizar registro
                $post->update($params_array);
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'changes' => $params_array
                ];
            }
            //retornar respuesta
        }
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request){
        $user =$this->getIdentity($request);
        //conseguir el post
        $post = Post::where('id',$id)
                    ->where('user_id', $user->sub)
                    ->first();
        if (!empty($post)) {
            //borrar registro
            $post->delete();
            //reotornar respuesta
            $data = [
                'code' => 200,
                'status'=>  'success',
                'post' => $post
            ];
        }else{
            $data = [
                'code' => 404,
                'status'=>  'error',
                'message' => 'El post no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    private function getIdentity($request){
        $jwtAuth = new JWTAuth($request);
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }

    public function upload(Request $request){
        //recoger imagen 
        $image = $request->file('file0');
        //validar imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,gif'
        ]);
        //guardar imagen en disco
        if (!$image || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'error al subir la imagen'  
            ];
        }else{
            $image_name = time(). $image->getClientOriginalName();
            \Storage::disk('images')->put($image_name, \File::get($image));
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //retornar respuesta
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        //COmprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
        if ($isset) {
            //COnseguir imagen
            $file = \Storage::disk('images')->get($filename);
            return new Response($file, 200);
        }else{
            $data = [
                'code'=>404,
                'status' => 'error',
                'message' => 'No existe la imagen'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200); 
    }

    public function getPostsByUser($id){
        $posts = Post::where('user_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200); 
    }
}
