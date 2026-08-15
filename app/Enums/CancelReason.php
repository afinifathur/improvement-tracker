<?php

namespace App\Enums;

enum CancelReason: string
{
    case CustomerCancelled = 'customer_cancelled';
    case ManagementDecision = 'management_decision';
    case NoLongerRequired = 'no_longer_required';
    case Duplicate = 'duplicate';
    case External = 'external';
    case Internal = 'internal';
    case CarriedOver = 'carried_over';
    case Other = 'other';
}
