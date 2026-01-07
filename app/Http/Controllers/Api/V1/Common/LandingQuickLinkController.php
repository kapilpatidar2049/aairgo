<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Base\Filters\Master\CommonMasterFilter;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\Admin\LandingQuickLink;
use App\Models\Admin\LandingHeader;
use Illuminate\Support\Facades\Validator;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use DB;
use Auth;
use Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Base\Services\ImageUploader\ImageUploader;
use Illuminate\Support\Str;
use App\Models\Admin\Setting;
use App\Models\Languages;


class LandingQuickLinkController extends BaseController
{
    protected $imageUploader;

    protected $landingQuick;

 

    public function __construct(LandingQuickLink $landingQuickLink, ImageUploaderContract $imageUploader)
    {
        $this->landingQuickLink = $landingQuickLink;
        $this->imageUploader = $imageUploader;
    }


    public function showPrivacyPage(Request $request)
{
        // Step 1: Get default locale or fallback to 'en'
        $defaultLocale = Languages::where('default_status', true)->value('code') ?? 'en';

        // Step 2: Check for locale from request or session
        $selectedLocale = $request->input('locale', session('selectedLocale', $defaultLocale));
    
        // Step 3: Try to fetch localized privacy content
        $content = LandingQuickLink::where('locale', $selectedLocale)->value('privacy')
            ?? LandingQuickLink::where('locale', 'en')->value('privacy');
    
        // Step 4: If no content found, return error
        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Privacy policy content not found.',
                'data' => null
            ], 404);
        }
    
        // Step 5: Return JSON response with HTML content inside "data"
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $content // still HTML but inside JSON
        ]);
}

public function showTermsPage(Request $request)
{
        // Step 1: Get default locale or fallback to 'en'
        $defaultLocale = Languages::where('default_status', true)->value('code') ?? 'en';

        // Step 2: Check for locale from request or session
        $selectedLocale = $request->input('locale', session('selectedLocale', $defaultLocale));
    
        // Step 3: Try to fetch localized terms content
        $content = LandingQuickLink::where('locale', $selectedLocale)->value('terms')
            ?? LandingQuickLink::where('locale', 'en')->value('terms');
    
        // Step 4: If no content found, return error
        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Terms policy content not found.',
                'data' => null
            ], 404);
        }
    
        // Step 5: Return JSON response with HTML content inside "data"
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $content // still HTML but inside JSON
        ]);
}


}