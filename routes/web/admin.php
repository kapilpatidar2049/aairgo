<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Common\CountryController;
use App\Http\Controllers\Web\Admin\LanguagesController;
use App\Http\Controllers\Web\Admin\PeakZoneController;
use App\Http\Controllers\Web\Admin\MobileAppSettingController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreferencesController;
/*
|--------------------------------------------------------------------------
| SPA Auth Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with '/'.
| These routes use the root namespace 'App\Http\Controllers\Web'.
|
 */


Route::middleware('auth:sanctum', config('jetstream.auth_session'),)->group(function () {

    Route::controller(CountryController::class)->group(function () {
    Route::group(['prefix' => 'country', 'middleware' => 'permission:manage-country'], function () {
            Route::get('/', 'index')->name('countries.index');
            Route::get('/list', 'list')->name('countries.list');
            Route::middleware(['permission:add-country'])->get('/create', 'create')->name('countries.create');
            Route::post('/store', 'store')->name('countries.store');
            Route::middleware(['permission:toggle_service_Location'])->post('/toggle_status/{country}', 'toggleStatus')->name('countries.toggle');
            Route::middleware(['permission:edit-country'])->get('/{country}', 'edit')->name('countries.edit');
            Route::post('/update/{country}', 'update')->name('countries.update');
        });
    });

    Route::controller(LanguagesController::class)->group(function () {
    
        Route::group(['prefix' => 'languages', 'middleware' => 'permission:languages'], function () {
            Route::get('/', 'index')->name('languages.index');
            Route::middleware(['permission:add_languages'])->get('/create', 'create')->name('languages.create');
            Route::get('/list', 'list')->name('languages.list');
            Route::middleware(['permission:browse_languages'])->get('/browse/{id}', 'browse')->name('languages.browse');
            Route::get('load-translation/{id}','loadTranslation');
            Route::post('/store', 'store')->name('languages.store');
            Route::post('auto-translate/{id}','autoTranslate');
            Route::post('translate/update/{id}','updateTranslate');
            Route::post('auto-translate-all/{id}','autoTranslateAll');
            Route::put('/update/{language}', 'update')->name('language.update');
            Route::put('/status/{language}', 'status');
            Route::middleware(['permission:delete_languages'])->delete('/delete/{language}', 'delete');
            Route::get('download-translation/{id}','downloadTranslation');
            Route::post('default-set/{lang}','updateAppLocale');
        });

        Route::get('current-languages','CurrenetLanguagelist')->name('current-languages');
        Route::get('current-locations','serviceLocationlist')->name('current-locations');
        Route::get('current-notifications','adminNotification')->name('current-notifications');
        // mark-notification-as-read
        Route::post('mark-notification-as-read','readNotification')->name('read-notifications');
        
    });


    Route::get('user/permissions',[PermissionController::class, 'userPermissions']);

    Route::namespace('Admin')->group(function () {
        Route::group(['prefix' => 'subscription', 'middleware' => 'permission:manage-subscription'], function(){
            Route::get('/', 'SubscriptionController@index');
            Route::middleware('remove_empty_query')->get('/list', 'SubscriptionController@fetch');
            Route::middleware(['permission:add-subscription'])->get('/create', 'SubscriptionController@create');
            Route::middleware(['permission:edit-subscription'])->get('/edit/{plan}', 'SubscriptionController@getById');
            Route::post('/store', 'SubscriptionController@store');
            Route::post('/update/{plan}', 'SubscriptionController@update');
            Route::post('/update-status/{plan}', 'SubscriptionController@toggleStatus');
            Route::middleware('remove_empty_query')->get('/driver-plan-list/{driver}', 'SubscriptionController@driverSubscriptionList');
            Route::post('/driver-plan-expire/{driver}', 'SubscriptionController@expireSubsctiption');
            Route::middleware(['permission:delete-subscription'])->delete('/delete/{plan}', 'SubscriptionController@delete');
        });

        Route::group(['prefix' => 'peak_zone','middleware' =>'permission:peak-zone-view'], function () {
            // prefix('zone')->group(function () {
            Route::get('/', 'PeakZoneController@index');
            Route::get('/fetch', 'PeakZoneController@getAllZone');
            Route::middleware(['permission:peak-zone-map-view'])->get('/map/{zone}', 'PeakZoneController@zoneMapView');

            Route::post('update_status/{peak_zones}', 'PeakZoneController@updateStatus');
            Route::delete('/delete/{peak_zones}','PeakZoneController@destroy'); 

        });

        Route::controller(AirportController::class)->group (function(){

        Route::group(['prefix' => 'airport','middleware' =>'permission:view-airport'],function(){    
            Route::get('/', 'index');
            Route::get('/fetch', 'getAllAirports');
            Route::get('/list', 'list');
            Route::middleware(['permission:Map-view-Airport'])->get('/map/{id}', 'airportMapView');
            Route::middleware(['permission:Add-Airport'])->get('/create', 'create');
            Route::middleware(['permission:Edit-airport'])->get('/edit/{id}', 'getById');
            Route::post('update/{airport}', 'update');
            Route::post('store', 'store');
            Route::get('/{id}', 'getById');
            Route::middleware(['permission:Delete-Airport'])->delete('/delete/{airport}', 'delete');
            Route::post('/update-status/{airport}', 'toggleAirportStatus');
        });
    });

        Route::controller(MobileAppSettingController::class)->group(function () {
            Route::group(['prefix' => 'app_modules'], function () {
                Route::middleware(['permission:app_modules_view'])->get('/', 'index')->name('app_module.index');
                Route::get('/list', 'fetch')->name('app_module.fetch');
                Route::get('/listVehicles', 'fetchVehicleTypes')->name('app_module.listVehicles');
                Route::middleware(['permission:add_app_modules'])->get('/create', 'create')->name('app_module.create');
                Route::post('/store', 'store')->name('app_module.store');
                Route::middleware(['permission:toggle_app_modules'])->post('/update-status/{setting}', 'updateStatus')->name('app_module.toggle');
                Route::middleware(['permission:edit_app_modules'])->get('/edit/{setting}', 'getById')->name('app_module.edit');
                Route::post('/update/{setting}', 'update')->name('app_module.update');
                Route::middleware(['permission:delete_app_modules'])->delete('/delete/{setting}', 'delete');
            });
        });


        Route::group(['prefix' => 'preferences'], function () {
            Route::middleware(['permission:preference_view'])->get('/', [PreferencesController::class, 'index'])->name('preferences.index');
            Route::middleware(['permission:preference_view'])->get('/list', [PreferencesController::class, 'list'])->name('preferences.list');
            Route::middleware(['permission:toggle_preference'])->post('/update-status/{preference}', [PreferencesController::class, 'updateStatus'])->name('preferences.toggle');
            Route::post('/', [PreferencesController::class, 'store'])->name('preferences.store');
            Route::post('/{preference}', [PreferencesController::class, 'update'])->name('preferences.update');
            Route::delete('/{preference}', [PreferencesController::class, 'destroy']);
        });

    });
});