<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RouteNeedsPermission
{
    /**
     * The delimiter used to separate the permissions.
     *
     * @var string
     */
    const DELIMITER = '|';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $permissions
     * @param bool $requireAll
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions, $requireAll = false, $guard = null)
    {
        $permissions = explode(self::DELIMITER, $permissions);

        if (!is_bool($requireAll)) {
            $requireAll = filter_var($requireAll, FILTER_VALIDATE_BOOLEAN);
        }

        if(count($permissions)>1){
            $flag = false;
            
            foreach($permissions as $key=> $permission){
                if(!access($guard)->hasPermissions([$permission], $requireAll)){
                    $flag = true;
                }else{
                    $flag = false;
                    break;
                }
            }
            // dd($flag);

            if($flag){
                abort(Response::HTTP_FORBIDDEN);
            }
        }else{

            if (!access($guard)->hasPermissions($permissions, $requireAll)) {
                abort(Response::HTTP_FORBIDDEN);
            }

        }

        return $next($request);
    }
}
