<?php

namespace App\Http\Controllers;

use App\AddClientUser;
use App\ClientDetails;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Yajra\DataTables\DataTables;
// use App\ClientDetails;

class ClientUser extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pages.client.user.index');
    }

    public function anyData(Request $request)
    {
        // if ($request->ajax()) {
        $users = AddClientUser::where('users_support_id', $request->id)->orderBy('client_user_id', 'desc')->get();
        return Datatables::of($users)
            ->addIndexColumn()
            ->editColumn('created_by', function ($users) {
                $created_by = User::where('id', $users->created_by)->first('name');
                if (!empty($created_by->name)) {
                    return $created_by->name;
                } else {
                    return "---";
                }
            })->editColumn('user_status', function ($users) {
                return $users->user_status == 1 ?
                    '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#confirmeModal" class="btn-success btn btn-xs action-btn" id="' . URL::to('client-user/status/' . $users->client_user_id) . '">Active</a>' :
                    '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#confirmeModal" class="btn btn-xs btn-warning action-btn" id="' . URL::to('client-user/status/' . $users->client_user_id) . '">In Active</a>';
                // if ($users->user_status == 1) {
                //     return "<span class='text-success'> Active</span>";
                // } else {
                //     return "<span class='text-warning'>Not Active</span>";
                // }
            })->editColumn('last_modified', function ($users) {
                $last_modified = User::where('id', $users->last_modified)->first('name');
                if (!empty($last_modified->name)) {
                    return $last_modified->name;
                } else {
                    return "---";
                }
            })->addColumn('action', function ($users) {
                // $status_text = 'Deactivate';
                // if ($users->user_status == 1) {
                //     $status_text = 'Activate';
                // }
                // <a href="client/' . $users->client_user_id . '/edit" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i>' . $status_text . '</a>
                return '<div class="d-grid gap-2">
                <a href="javascript:void(0)" id="client-user/' . $users->client_user_id . '" data-bs-toggle="modal" data-bs-target="#deleteModal" class="btn btn-xs btn-danger"><i class="glyphicon glyphicon-delete"></i>Delete</a>

                </div>';
            })->rawColumns(['action', 'user_status'])->make(true);
        // } else {
        //     return abort(404);
        // }
    }

    // Update User Status
    public function status(Request $request)
    {
        $client_user = AddClientUser::where(['client_user_id' => $request->id])->first();
        if ($client_user->user_status == 1) {
            $user_status = 0;
            $data = '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#confirmeModal" class="btn btn-xs btn-warning action-btn" id="' . 'client-user/status/' . $request->id . '">In Active</a>';
        } else {
            $data = '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#confirmeModal" class="btn-success btn btn-xs action-btn" id="' . 'client-user/status/' . $request->id . '">Active</a>';
            $user_status = 1;
        }

        $status = AddClientUser::where('client_user_id', $request->id)->update([
            'user_status'   => $user_status,
            'modified_by'   => Auth::user()->id,
            'last_modified' => date('d-m-Y H:i:s'),
        ]);
        if ($status) {
            return response()->json(
                [
                    'status' => true,
                    'data'   => $data,
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => false,
                    'data'   => 'error',
                ]
            );
        }
    }
    // Update User Status

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $users_support_id = $request->id;
        return view('pages.client.user.add', compact('users_support_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rule = [
            'users_support_id' => 'required|max:250',
            'user_type'        => 'required|max:250',
            'remark'           => 'max:250',
        ];

        $duplicateUser = AddClientUser::where(
            [
                'users_support_id' => $request->users_support_id,
                'clients_username' => $request->user_name,
            ]
        )->get();
        if (count($duplicateUser) > 0) {
            $rule['user_name'] = 'required|unique:App\AddClientUser,clients_username|max:250';
        } else {
            $rule['user_name'] = 'required|max:250';
        }
        // return $request;
        $request->validate($rule);

        try {
            $server_id = ClientDetails::where('support_id', $request->users_support_id)->pluck('clients_server_name');
            $user = new AddClientUser();
            $user->users_support_id     = $request->users_support_id;
            $user->clients_username     = $request->user_name;
            $user->user_type           = $request->user_type;
            // $user->users_joining_date   = $request->user_name;
            $user->remark               = $request->remark;
            $user->users_server_name    = $server_id[0];
            $user->created_by           = Auth::user()->id;
            $user->modified_by          = Auth::user()->id;
            $user->save();
        } catch (\Throwable $th) {
            toastr()->error("Server Error " . $th->getMessage());
            return redirect(route('user-create', ['id' => $request->users_support_id]));
        }
        toastr()->success('User successfully added!!');
        return redirect(route('user-create', ['id' => $request->users_support_id]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,  $id)
    {
        $users_support_id = $request->id;
        $client_user = AddClientUser::where('client_user_id', $id)->first();
        return view('pages.client.user.edit', compact('users_support_id', 'client_user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'users_support_id' => 'required|max:250',
            'user_name'        => 'required|max:250',
            'user_type'        => 'required|max:250',
            'remark'           => 'max:250',
        ]);
        try {
            // $server_id = ClientDetails::where('support_id', $request->users_support_id)->pluck('clients_server_name');
            $data = [
                'users_support_id'   =>  $request->users_support_id,
                'clients_username'   =>  $request->user_name,
                'user_type'          =>  $request->user_type,
                'modified_by'        =>  Auth::user()->id,
                // 'users_joining_date' =>  $request->joining_date,
                // 'users_server_name'  =>  $server_id[0],
                'remark'             =>  $request->remark,
            ];
            $update = AddClientUser::where('client_user_id', $id)->update($data);
        } catch (\Throwable $th) {
            toastr()->error("Server Error " . $th->getMessage());
            return redirect(route('user-create', ['id' => $request->users_support_id]));
        }
        toastr()->success('User Successfully Updated!!');
        return redirect('client/' . $request->users_support_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            AddClientUser::where('client_user_id', $id)->delete();
            toastr()->success('Client User Successfully Deleted');
        } catch (\Throwable $th) {
            //throw $th;
            toastr()->error('Server Error : ' . $th->getMessage());
        }
        return redirect('client/' . $request->client_id);
    }
}
