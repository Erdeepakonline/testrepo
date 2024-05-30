<?php







namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class MessengerController extends Controller



{


    /**
    * @OA\Get(
    *     path="/api/messenger_list",
    *     summary="Get list of active messengers",
    *     tags={"Messengers"},
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="id", type="string"),
    *                     @OA\Property(property="value", type="string")
    *                 )
    *             ),
    *             @OA\Property(property="msg", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="msg", type="string")
    *         )
    *     )
    * )
    */
    public function MessengerList()
    {
       $result = DB::table('messengers')->select('messenger_name as id', 'messenger_name as value')->where('status', 1)->orderBy('id','desc')->get()->toArray();
       if (count($result)) {
        $return['code'] = 200;
        $return['data'] = $result;
        $return['msg'] = 'Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
     return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * @OA\Post(
    *     path="/api/add_messenger",
    *     summary="Add or update a messenger",
    *     tags={"Messengers"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(property="messenger_name", type="string", description="Name of the messenger")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation Error",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="error", type="object", description="Validation errors"),
    *             @OA\Property(property="msg", type="string", description="Message indicating validation error")
    *         )
    *     )
    * )
    */
    public function addMessenger(Request $request){
        $result = DB::table('messengers')->find($request->id);
        if($request->id){
            $validator = Validator::make(
                $request->all(),
                [
                    'messenger_name' => 'required|unique:messengers,messenger_name,'.$result->id.'id',
                ]
            );
         }else{
            $validator = Validator::make(
                $request->all(),

                [

                    'messenger_name' => 'required|unique:messengers,messenger_name',

                ]

            );

        }

        if ($validator->fails()) {

            $return['code']    = 100;

            $return['error']   = $validator->errors();

            $return['msg'] = 'Validation Error';

            return json_encode($return);

        }

        if($request->id){

             $insert =  DB::table('messengers')->where('id',$request->id)->update([

                'messenger_name' => $request->messenger_name

            ]);

            $return['code'] = 200;

            $return['msg'] = 'Updated Successfully !';

            return json_encode($return);

        }else{

            $insert =  DB::table('messengers')->insert([

                'messenger_name' => $request->messenger_name,

                'status' => true

            ]);

        }

            $return['code'] = 200;

            $return['msg'] = 'insert Data Successfully!';

            return json_encode($return, JSON_NUMERIC_CHECK);

    }


    /**
    * @OA\Post(
    *     path="/api/delete_messenger",
    *     summary="Delete a messenger",
    *     tags={"Messengers"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="Messenger ID and Status",
    *         @OA\JsonContent(
    *             required={"id", "status"},
    *             @OA\Property(property="id", type="integer", example="1"),
    *             @OA\Property(property="status", type="boolean", example="false"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Messenger deleted successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example="200"),
    *             @OA\Property(property="msg", type="string", example="Deleted Successfully!"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Messenger deletion unsuccessful",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example="101"),
    *             @OA\Property(property="msg", type="string", example="Deletion Not Successful!"),
    *         ),
    *     ),
    * )
    */
    public function deleteMessenger(Request $request){ 
        $deleted = DB::table('messengers')->where('id',$request->id)->update(['status' => $request->status]);
        if($deleted){
            $return['code'] = 200;
            $return['msg'] = 'Deleted Successfully!';
            return json_encode($return);
        }else{
            $return['code'] = 101;
            $return['msg'] = 'Deleted Not Successfully!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
    }


    /**
    * @OA\Post(
    *     path="/api/status-update-messenger",
    *     summary="Update status of a messenger",
    *     tags={"Messengers"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="Messenger ID and Status",
    *         @OA\JsonContent(
    *             required={"id", "status"},
    *             @OA\Property(property="id", type="integer", example="1"),
    *             @OA\Property(property="status", type="boolean", example="true"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Status updated successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example="200"),
    *             @OA\Property(property="msg", type="string", example="Status Updated Successfully!"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Status update unsuccessful",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example="101"),
    *             @OA\Property(property="msg", type="string", example="Status Updated Not Successfully!"),
    *         ),
    *     ),
    * )
    */
    public function updateStatusMessenger(Request $request){ 
        $update = DB::table('messengers')->where('id',$request->id)->update(['status' => $request->status]);
        if($update){
            $return['code'] = 200;
            $return['msg'] = 'Status Updated Successfully!';
            return json_encode($return);
        }else{
            $return['code'] = 101;
            $return['msg'] = 'Status Updated Not Successfully!';
            return json_encode($return, JSON_NUMERIC_CHECK);
        }
    }


    /**
    * @OA\Post(
    *     path="/api/admin_messenger_list",
    *     summary="Get list of messengers",
    *     tags={"Messengers"},
    *     @OA\RequestBody(
    *         required=false,
    *         description="Optional request parameters",
    *         @OA\JsonContent(
    *             @OA\Property(property="lim", type="integer", example="10"),
    *             @OA\Property(property="page", type="integer", example="1"),
    *             @OA\Property(property="src", type="string", example="keyword"),
    *             @OA\Property(property="status", type="integer", example="1"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Messengers found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example="200"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="messenger_name", type="string"),
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="status", type="integer"),
    *                 )
    *             ),
    *             @OA\Property(property="row", type="integer", example="10"),
    *             @OA\Property(property="msg", type="string", example="Successfully found!"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="No messengers found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example="100"),
    *             @OA\Property(property="msg", type="string", example="Data Not found!"),
    *         ),
    *     ),
    * )
    */
    public function MessengerListget(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $status = $request->status;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $result = DB::table('messengers')->select('messenger_name','id','status');
        if($src){
            $result->whereRaw('concat(ss_messengers.messenger_name) like ?', "%{$src}%");
        }
        if(strlen($status) > 0){  
            $result->where('status',$status);
        }
        else{
            $result->where('status','!=',2)->orderBy('id','asc')->offset($start)->limit($limit)->get()->toArray();
        }
        $res = $result->where('status','!=',2)->orderBy('id','asc')->offset($start)->limit($limit)->get()->toArray();
        $row = $result->get()->toArray();
       if (count($res)>0) {
        $return['code'] = 200;
        $return['data'] = $res;
        $return['row'] = count($row);
        $return['msg'] = 'Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
     return json_encode($return, JSON_NUMERIC_CHECK);
    }
}

 

