<?php

namespace App\Base\Constants\Masters;

class DriverDocumentStatusString
{
    const UPLOADED_AND_DECLINED = 'uploaded_and_declined';
    const UPLOADED_AND_APPROVED = 'uploaded_and_approved';
    const NOT_UPLOADED = 'not_uploaded';
    const UPLOADED_AND_WAITING_FOR_APPROVAL = 'waiting_for_approval';
    const REUPLOADED_AND_WAITING_FOR_APPROVAL = 'reuploaded_and_waiting_for_approval';
    const REUPLOADED_AND_DECLINED = 'reuploaded_and_declined';
    const EXPIRED_AND_DECLINED = 'expired_and_declined';
}
