<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Access\Role;
use Illuminate\Http\Request;
use App\Models\Access\Permission;
use App\Base\Constants\Auth\Permission as PermissionSlug;

class PermissionController extends Controller
{
    // public function index($id)
    // {
    //     $role = Role::find($id);
    //     $permissions = Permission::all();
    //     $permissionRole = $role->permissions()->get();

    //     return Inertia::render('pages/roles/permission', [
    //         'role' => $role,
    //         'permissions' => $permissions,
    //         'permissionRole' => $permissionRole,
    //     ]);
    // }

    public function index($id)
{
    $role = Role::find($id);
    $currentUserRole = auth()->user()->roles()->first();

    $otherPermissions = [
        PermissionSlug::ADD_SERVICE_LOCATION,
        PermissionSlug::WEB_CREATE_BOOKING,
        PermissionSlug::VIEW_WEB_PROFILE,
        PermissionSlug::VIEW_WEB_HISTORY,
        PermissionSlug::VIEW_WEB_HISTORY_DETAIL ,
        PermissionSlug::VIEW_WEB_SUPPORT,
        PermissionSlug::CREATE_WEB_SUPPORT_TICKET ,
        PermissionSlug::VIEW_WEB_SUPPORT_TICKET_DETAIL ,
    ];

    if ($currentUserRole->slug === 'super-admin') {
        // Super admin can see all permissions
        $permissions = Permission::whereNotIn('slug',$otherPermissions )->get();
    } else {
        // Admins only see their assigned permissions
        $allowedPermissionSlugs = $currentUserRole->permissions()->pluck('slug');
        $permissions = Permission::whereNotIn('slug',$otherPermissions )->whereIn('slug', $allowedPermissionSlugs)->get();
    }

    $permissionRole = $role->permissions()->get();
    return Inertia::render('pages/roles/permission', [
        'role' => $role,
        'permissions' => $permissions,
        'permissionRole' => $permissionRole,
    ]);
}

    public function store(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');

        $role->permissions()->sync($permissions);


        // return redirect()->route('roles.index');
        return response()->json([
            'message' => 'Permissions synced successfully',
        ]);
    }


    /**
     * User's permissions
     * 
     * */
    // public function userPermissions()
    // {

    //     $role = auth()->user()->roles()->first();
    //     // dd($role);

    //     if($role->slug=='super-admin'){

    //       $permissions = Permission::pluck('slug')->all();  

    //     }else{

    //     $permissions = $role->permissions()->pluck('slug')->toArray();

    //     }
    //     // dd($permissions);


    //     return response()->json([
    //         'success'=>true,
    //         'message' => 'Permissions listed successfully',
    //         'data'=>$permissions
    //     ]);


    // }
    public function userPermissions()
{
    $role = auth()->user()->roles()->first();

    if ($role->slug === 'super-admin') {
        // Super admin can see all permissions
        $otherPermissions = [
            PermissionSlug::WEB_CREATE_BOOKING,
            PermissionSlug::VIEW_WEB_PROFILE,
            PermissionSlug::VIEW_WEB_HISTORY,
            PermissionSlug::VIEW_WEB_HISTORY_DETAIL ,
            PermissionSlug::VIEW_WEB_SUPPORT,
            PermissionSlug::CREATE_WEB_SUPPORT_TICKET ,
            PermissionSlug::VIEW_WEB_SUPPORT_TICKET_DETAIL ,
            PermissionSlug::DISPATHCER_DASHBOARD,
            PermissionSlug::DISPATHCER_DRIVERS,
            PermissionSlug::DISPATHCER_RIDE,
            PermissionSlug::DISPATHCER_RIDE_REQUEST ,
            PermissionSlug::DISPATHCER_RIDE_REQUEST_VIEW ,
            PermissionSlug::DISPATHCER_RIDE_REQUEST_CANCEL ,
            PermissionSlug::DISPATHCER_RIDE_REQUEST_ASSIGN ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST_VIEW ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST_CANCEL ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST_ASSIGN ,
            PermissionSlug::DISPATHCER_CHAT,
            PermissionSlug::DISPATHCER_SCHEDULED_RIDES,
            PermissionSlug::DISPATHCER_REQUEST_ENQUIRY,
        ];
        $permissions = Permission::whereNotIn('slug',$otherPermissions )->pluck('slug')->all();
        $permissions[] = 'super-admin';
    } else {
        // Other admins see only the permissions assigned to their role
        $permissions = $role->permissions()->pluck('slug')->toArray();
    }

    return response()->json([
        'success' => true,
        'message' => 'Permissions listed successfully',
        'data' => $permissions
    ]);
}
}
