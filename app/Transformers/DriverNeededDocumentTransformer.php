<?php

namespace App\Transformers;

use App\Models\Admin\DriverDocument;
use App\Models\Admin\DriverNeededDocument;
use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Transformers\Driver\DriverDocumentTransformer;
use App\Base\Constants\Masters\DriverDocumentStatusString;
use App\Transformers\Transformer;

class DriverNeededDocumentTransformer extends Transformer
{
    /**
    * Resources that can be included if requested.
    *
    * @var array
    */
    protected array $availableIncludes = [
        'driver_document',
    ];
    /**
     * Resources that can be included default.
     *
     * @var array
     */
    protected array $defaultIncludes = [
        'driver_document',
    ];
    /**
     * A Fractal transformer.
     *
     * @param DriverNeededDocument $driverneededdocument
     * @return array
     */
    public function transform(DriverNeededDocument $driverneededdocument)
    {
        $userlang = 'en';
        if(auth()->user()){
            $userlang = auth()->user()->lang ?? 'en';
        }

        $params =  [
            'id'=>$driverneededdocument->id,
            'name' => $driverneededdocument->name,
            'doc_type' => $driverneededdocument->doc_type,
            'has_identify_number' => (bool)$driverneededdocument->has_identify_number,
            'has_expiry_date' => (bool) $driverneededdocument->has_expiry_date,
            'active' => $driverneededdocument->active,
            'identify_number_locale_key'=>$driverneededdocument->identify_number_locale_key,
            'is_editable' => $driverneededdocument->is_editable == 1,
            'document_status'=>2,
            'is_required' => $driverneededdocument->is_required == 1,
            'document_status_string'=>custom_status_trans(DriverDocumentStatusString::NOT_UPLOADED,[],$userlang)
        ];

        $driver_document = DriverDocument::where('document_id', $driverneededdocument->id)->where('driver_id', auth()->user()->driver->id)->first();
       
        $params['is_uploaded'] = false;
        $params['is_front_and_back'] = false;


        if($driverneededdocument->image_type=='front_and_back')
        {
            $params['is_front_and_back'] = true;
            $params['document_name_front'] = $driverneededdocument->document_name_front;
            $params['document_name_back'] = $driverneededdocument->document_name_back;

        }

        if ($driver_document) {
            $uploaded_status = [
                DriverDocumentStatus::UPLOADED_AND_APPROVED,
                DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL,
                DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL,
            ];
            $params['is_uploaded'] = in_array($driver_document->document_status,$uploaded_status);
            $params['document_status']= $driver_document->document_status;

            foreach (DriverDocumentStatus::DocumentStatus() as $key=> $document_string) {
                if ($driver_document->document_status==$key) {
                    $params['document_status_string'] = custom_status_trans($document_string,[],$userlang);
                }
            }
        }

        return $params;
    }

    /**
     * Include the driver document of the driver needed document.
     *
     * @param DriverNeededDocument $driverneededdocument
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeDriverDocument(DriverNeededDocument $driverneededdocument)
    {
        $roles = $driverneededdocument->driverDocument()->where('driver_id', auth()->user()->driver->id)->first();

        return $roles
        ? $this->item($roles, new DriverDocumentTransformer)
        : $this->null();
    }
}
