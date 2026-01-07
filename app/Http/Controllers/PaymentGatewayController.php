<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use App\Models\ThirdPartySetting;

class PaymentGatewayController  extends Controller
{
    public function index() 
    {
        $settings = ThirdPartySetting::where('module', 'payment')
            ->pluck('value', 'name')
            ->toArray();
    // dd($settings);
        // Transform settings into a structured object
        $formattedSettings = [
            'enable_paystack' => filter_var($settings['enable_paystack'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'paystack_test_publish_key' => $settings['paystack_test_publish_key'] ?? '',
            'paystack_production_publish_key' => $settings['paystack_production_publish_key'] ?? '',
            'paystack_test_secret_key' => $settings['paystack_test_secret_key'] ?? '',
            'paystack_production_secret_key' => $settings['paystack_production_secret_key'] ?? '',
            'paystack_environment' => $settings['paystack_environment'] ?? '',
    
            'enable_cashfree' => filter_var($settings['enable_cashfree'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'cash_free_environment' => $settings['cash_free_environment'] ?? '',
            'cash_free_secret_key' => $settings['cash_free_secret_key'] ?? '',
            'cash_free_production_secret_key' => $settings['cash_free_production_secret_key'] ?? '',
            'cash_free_app_id' => $settings['cash_free_app_id'] ?? '',
            'cash_free_production_app_id' => $settings['cash_free_production_app_id'] ?? '',
    
            'enable_mercadopago' => filter_var($settings['enable_mercadopago'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'mercadopago_environment' => $settings['mercadopago_environment'] ?? '',
            'mercadopago_test_public_key' => $settings['mercadopago_test_public_key'] ?? '',
            'mercadopago_live_public_key' => $settings['mercadopago_live_public_key'] ?? '',
            'mercadopago_test_access_token' => $settings['mercadopago_test_access_token'] ?? '',
            'mercadopago_live_access_token' => $settings['mercadopago_live_access_token'] ?? '',
    
            'enable_stripe' => filter_var($settings['enable_stripe'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'enable_stripe_authorization' => filter_var($settings['enable_stripe_authorization'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'stripe_environment' => $settings['stripe_environment'] ?? '',
            'stripe_test_secret_key' => $settings['stripe_test_secret_key'] ?? '',
            'stripe_live_secret_key' => $settings['stripe_live_secret_key'] ?? '',
            'stripe_test_publishable_key' => $settings['stripe_test_publishable_key'] ?? '',
            'stripe_live_publishable_key' => $settings['stripe_live_publishable_key'] ?? '',
    
            'enable_flutterwave' => filter_var($settings['enable_flutterwave'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'flutter_wave_environment' => $settings['flutter_wave_environment'] ?? '',
            'flutter_wave_test_secret_key' => $settings['flutter_wave_test_secret_key'] ?? '',
            'flutter_wave_production_secret_key' => $settings['flutter_wave_production_secret_key'] ?? '',
    
            'enable_razorpay' => filter_var($settings['enable_razorpay'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'razor_pay_environment' => $settings['razor_pay_environment'] ?? '',
            'razor_pay_test_api_key' => $settings['razor_pay_test_api_key'] ?? '',
            'razor_pay_live_api_key' => $settings['razor_pay_live_api_key'] ?? '',
            'razor_pay_secrect_key' => $settings['razor_pay_secrect_key'] ?? '',
            'razor_pay_test_secrect_key' => $settings['razor_pay_test_secrect_key'] ?? '',
    
            'enable_khalti' => filter_var($settings['enable_khalti'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'khalti_pay_environment' => $settings['khalti_pay_environment'] ?? '',
            'khalti_pay_test_api_key' => $settings['khalti_pay_test_api_key'] ?? '',
            'khalti_pay_live_api_key' => $settings['khalti_pay_live_api_key'] ?? '',

            // 'enable_easypaisa' => filter_var($settings['enable_easypaisa'] ?? false, FILTER_VALIDATE_BOOLEAN),
            // 'easypay_environment' => $settings['easypay_environment'] ?? '',
            // 'easypaisa_store_id' => $settings['easypaisa_store_id'] ?? '',
            // 'easypaisa_hash_key' => $settings['easypaisa_hash_key'] ?? '',

            'enable_xendit' => filter_var($settings['enable_xendit'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'xendi_pay_environment' => $settings['xendi_pay_environment'] ?? '',
            'xendi_pay_test_api_key' => $settings['xendi_pay_test_api_key'] ?? '',
            // 'xendi_pay_test_secrect_key' => $settings['xendi_pay_test_secrect_key'] ?? '',
            'xendit_pay_live_api_key' => $settings['xendit_pay_live_api_key'] ?? '',
            // 'xendit_pay_secrect_key' => $settings['xendit_pay_secrect_key'] ?? '',


            'enable_flexpaie' => filter_var($settings['enable_flexpaie'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'flexpaie_environment' => $settings['flexpaie_environment'] ?? '',
            'flexpaie_test_bearer_token' => $settings['flexpaie_test_bearer_token'] ?? '',
            'flexpaie_production_bearer_token' => $settings['flexpaie_production_bearer_token'] ?? '',


            'enable_openpix' => filter_var($settings['enable_openpix'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'openpix_environment' => $settings['openpix_environment'] ?? '',
            'openpix_test_api_key' => $settings['openpix_test_api_key'] ?? '',
            'openpix_live_api_key' => $settings['openpix_live_api_key'] ?? '',
            'openpix_webhook_url' => route('mercadopago.pix.webhook'),

            'enable_myfatoora' => filter_var($settings['enable_myfatoora'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'myfatoora_environment' => $settings['myfatoora_environment'] ?? '',
            'myfatoora_test_token' => $settings['myfatoora_test_token'] ?? '',
            'myfatoora_live_token' => $settings['myfatoora_live_token'] ?? '',

            'enable_paymongo' => filter_var($settings['enable_paymongo'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'paymongo_environment' => $settings['paymongo_environment'] ?? '',
            'paymongo_test_secret_key' => $settings['paymongo_test_secret_key'] ?? '',
            'paymongo_live_secret_key' => $settings['paymongo_live_secret_key'] ?? '',

            // 'enable_airtel' => filter_var($settings['enable_airtel'] ?? false, FILTER_VALIDATE_BOOLEAN),
            // 'airtel_environment' => $settings['airtel_environment'] ?? '',
            // 'airtel_test_client_id' => $settings['airtel_test_client_id'] ?? '',
            // 'airtel_test_client_secret_key' => $settings['airtel_test_client_secret_key'] ?? '',
            // 'airtel_live_client_id' => $settings['airtel_live_client_id'] ?? '',
            // 'airtel_live_client_secret_key' => $settings['airtel_live_client_secret_key'] ?? '',


            'enable_paypal' => filter_var($settings['enable_paypal'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'paypal_environment' => $settings['paypal_environment'] ?? '',
            'paypal_sandbox_client_id' => $settings['paypal_sandbox_client_id'] ?? '',
            'paypal_sandbox_client_secret' => $settings['paypal_sandbox_client_secret'] ?? '',
            'paypal_sandbox_app_id' => $settings['paypal_sandbox_app_id'] ?? '',
            'paypal_client_id' => $settings['paypal_client_id'] ?? '',
            'paypal_client_secret' => $settings['paypal_client_secret'] ?? '',
            'paypal_app_id' => $settings['paypal_app_id'] ?? '',
            'paypal_notify_url' => $settings['paypal_notify_url'] ?? '',


            'enable_fedapay' => filter_var($settings['enable_fedapay'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'fedapay_environment' => $settings['fedapay_environment'] ?? '',
            'fedapay_test_secret_key' => $settings['fedapay_test_secret_key'] ?? '',
            'fedapay_live_secret_key' => $settings['fedapay_live_secret_key'] ?? '',
            
        ];
    
        return Inertia::render('pages/payment_gateway/index', [
            'app_for'=>env('APP_FOR'),
            'settings' => $formattedSettings,
        ]);
    }
    

    public function update(Request $request)
    {
        // dd($request->all());
        $settings = $request->validate([
            'enable_paystack' => "required",
            'paystack_environment' => "required",
            'paystack_test_secret_key' => "sometimes",
            'paystack_production_secret_key' => "sometimes",
            'paystack_test_publish_key' => "sometimes",
            'paystack_production_publish_key' => "sometimes",

            'enable_cashfree' => "required",
            'cash_free_environment' => "required",
            'cash_free_secret_key' => "sometimes",
            'cash_free_production_secret_key' => "sometimes",
            'cash_free_app_id' => "sometimes",
            'cash_free_production_app_id' => "sometimes",
    
            'enable_mercadopago' => "required",
            'mercadopago_environment' => "required",
            'mercadopago_test_public_key' => "sometimes",
            'mercadopago_live_public_key' => "sometimes",
            'mercadopago_test_access_token' => "sometimes",
            'mercadopago_live_access_token' => "sometimes",
    
            'enable_stripe' => "required",
            'enable_stripe_authorization' => "required",
            'stripe_environment' => "required",
            'stripe_test_secret_key' => "sometimes",
            'stripe_live_secret_key' => "sometimes",
            'stripe_test_publishable_key' => "sometimes",
            'stripe_live_publishable_key' => "sometimes",
    
            'enable_flutterwave' => "required",
            'flutter_wave_environment' => "required",
            'flutter_wave_test_secret_key' => "sometimes",
            'flutter_wave_production_secret_key' => "sometimes",
    
            'enable_razorpay' => "required",
            'razor_pay_environment' => "required",
            'razor_pay_test_api_key' => "sometimes",
            'razor_pay_live_api_key' => "sometimes",
            'razor_pay_secrect_key' => "sometimes",
            'razor_pay_test_secrect_key' => "sometimes",
    
            'enable_khalti' => "required",
            'khalti_pay_environment' => "required",
            'khalti_pay_test_api_key' => "sometimes",
            'khalti_pay_live_api_key' => "sometimes",

            'enable_xendit' => "required",
            'xendi_pay_environment' => "sometimes",
            'xendi_pay_test_api_key' => "sometimes",
            // 'xendi_pay_test_secrect_key' => "sometimes",
            'xendit_pay_live_api_key' => "sometimes",
            // 'xendit_pay_secrect_key' => "sometimes",

            // 'easypay_environment' => "required",
            // 'easypaisa_store_id' => "sometimes",
            // 'easypaisa_hash_key' => "sometimes",
            
            'enable_flexpaie' => "required",
            'flexpaie_environment' => "required",
            'flexpaie_test_bearer_token' => "sometimes",
            'flexpaie_production_bearer_token' => "sometimes",


            'enable_openpix' => "required",
            'openpix_environment' => "required",
            'openpix_test_api_key' => "sometimes",
            'openpix_live_api_key' => "sometimes",

            'enable_myfatoora' => "required",
            'myfatoora_environment' => "required",
            'myfatoora_test_token' => "sometimes",
            'myfatoora_live_token' => "sometimes",

            'enable_paymongo' => "required",
            'paymongo_environment' => "required",
            'paymongo_test_secret_key' => "sometimes",
            'paymongo_live_secret_key' => "sometimes",

            // 'enable_airtel' => "required",
            // 'airtel_environment' => "required",
            // 'airtel_test_client_id' => "sometimes",
            // 'airtel_test_client_secret_key' => "sometimes",
            // 'airtel_live_client_id' => "sometimes",
            // 'airtel_live_client_secret_key' => "sometimes",


            'enable_paypal' => "required",
            'paypal_environment' => "required",
            'paypal_sandbox_client_id' => "sometimes",
            'paypal_sandbox_client_secret' => "sometimes",
            'paypal_sandbox_app_id' => "sometimes",
            'paypal_client_id' => "sometimes",
            'paypal_client_secret' => "sometimes",
            'paypal_app_id' => "sometimes",
            'paypal_notify_url' => "sometimes",

            'enable_fedapay' => "required",
            'fedapay_environment' => "required",
            'fedapay_test_secret_key' => "sometimes",
            'fedapay_live_secret_key' => "sometimes",

        ]);

        // dd($settings);

        ThirdPartySetting::where('module', 'payment')->delete(); // corrected delete command

        $paypal_settings = [
            'paypal_environment'=>$request->paypal_environment,
            'paypal_sandbox_client_id'=>$request->paypal_sandbox_client_id,
            'paypal_sandbox_client_secret'=>$request->paypal_sandbox_client_secret,
            'paypal_sandbox_app_id'=>$request->paypal_sandbox_app_id,
            'paypal_live_client_id'=>$request->paypal_client_id,
            'paypal_live_client_secret'=>$request->paypal_client_secret,
            'paypal_live_app_id'=>$request->paypal_app_id,
            'paypal_notify_url'=>$request->paypal_notify_url,
        ];

        $this->updateEnvFile($paypal_settings);
        

        foreach ($settings as $key => $setting) 
        {
            // dd($setting);

            ThirdPartySetting::create(['name' => $key, 'value' => $setting, 'module' => 'payment']);                 
        }

        return response()->json(['message' => 'Sms  Destails updated successfully'], 201);

    }

    /**
     * Update the .env file with new settings.
     *
     * @param array $settings
     * @return void
     */
    private function updateEnvFile(array $settings)
    {
        // Get the path to the .env file
        $envPath = base_path('.env');

        // Check if the .env file exists
        if (file_exists($envPath)) {
            // Read the current content of the .env file
            $envContent = file_get_contents($envPath);

            // Update or add each setting in the .env file
            foreach ($settings as $key => $value) {
                $envKey = strtoupper($key); // Convert the key to uppercase to match the .env convention

                // Create a regex pattern to match the existing key-value pair
                $pattern = "/^{$envKey}=[^\r\n]*/m";

                // If the key exists, replace it; otherwise, append the new key-value pair
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$envKey}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$envKey}={$value}";
                }
            }

            // Write the updated content back to the .env file
            file_put_contents($envPath, $envContent);
        }
    }
}
