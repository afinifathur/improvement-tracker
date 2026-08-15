<?php

namespace App\Enums;

enum BlockedReason: string
{
    case WaitingMaterial = 'waiting_material';
    case WaitingSparepart = 'waiting_sparepart';
    case WaitingApproval = 'waiting_approval';
    case WaitingVendor = 'waiting_vendor';
    case MachineUnavailable = 'machine_unavailable';
    case Manpower = 'manpower';
    case ExternalDependency = 'external_dependency';
    case Other = 'other';
}
