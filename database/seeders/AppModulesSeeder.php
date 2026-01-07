<?php

namespace Database\Seeders;

use App\Models\Master\MobileAppSetting;
use App\Models\Admin\GoodsTypeTranslation;
use App\Models\Admin\Promo;
use App\Models\Admin\PromoCodeUser;
use Illuminate\Database\Seeder;

class AppModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected $app_modules = [
        [   'name' => 'Taxi',
            'transport_type' => 'taxi',
            'service_type' => 'normal',
            'order_by' => '1',
            'short_description' => 'Normal Taxi',
            'description' => 'Normal Taxi',
            'mobile_menu_icon' => 'taxi.png',
            'active' => 1,
        ],
         [  'name' => 'Delivery',
            'transport_type' => 'delivery',
            'service_type' => 'normal',
            'order_by' => '2',
            'short_description' => 'Normal Delivery',
            'description' => 'Normal Delivery',
            'mobile_menu_icon' => 'delviery.png',
            'active' => 1,
        ],
         [  'name' => 'Rental',
            'transport_type' => 'taxi',
            'service_type' => 'rental',
            'order_by' => '3',
            'short_description' => 'Rental Taxi',
            'description' => 'rental Taxi',
            'mobile_menu_icon' => 'rental.png',
            'active' => 1,
        ],
         [   'name' => 'Delivery Rental',
            'transport_type' => 'delivery',
            'service_type' => 'rental',
            'order_by' => '4',
            'short_description' => 'Rental Delivery',
            'description' => 'Rental Delivery',
            'mobile_menu_icon' => 'rental.png',
            'active' => 1,
        ],
         [   'name' => 'Outstation',
            'transport_type' => 'taxi',
            'service_type' => 'outstation',
            'order_by' => '5',
            'short_description' => 'Outstation Taxi',
            'description' => 'Outstation Taxi',
            'mobile_menu_icon' => 'outstation.png',
            'active' => 1,
        ],
         [   'name' => 'Delivery Outstation',
            'transport_type' => 'delivery',
            'service_type' => 'outstation',
            'order_by' => '6',
            'short_description' => 'Outstation Delivery',
            'description' => 'Outstation Delivery',
            'mobile_menu_icon' => 'outstation.png',
            'active' => 1,
        ],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $created_params = $this->app_modules;

        $value = MobileAppSetting::first();
        if(!$value){
            foreach ($created_params as $modules) 
            {
                $value = MobileAppSetting::create($modules);
            }
        }

    }
}
