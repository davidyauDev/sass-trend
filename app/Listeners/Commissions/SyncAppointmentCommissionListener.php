<?php

namespace App\Listeners\Commissions;

use App\Actions\Commissions\GenerateCommissionAction;
use App\Actions\Commissions\ReverseCommissionAction;
use App\DTOs\Commissions\CommissionGenerationData;
use App\Events\Commissions\AppointmentCommissionStatusChanged;
use App\Models\ProfessionalCommission;
use App\Services\Agenda\AppointmentStatusCatalog;
use App\Services\Commissions\CommissionSourceCatalog;

final class SyncAppointmentCommissionListener
{
    public function __construct(
        private readonly GenerateCommissionAction $generateCommission,
        private readonly ReverseCommissionAction $reverseCommission,
    ) {}

    public function handle(AppointmentCommissionStatusChanged $event): void
    {
        $appointment = $event->appointment->loadMissing(['branch', 'professional', 'service', 'status']);

        if ($event->statusSlug === AppointmentStatusCatalog::COMPLETED) {
            if ($appointment->professional_id === null) {
                return;
            }

            $this->generateCommission->handle($event->actor, new CommissionGenerationData(
                branchId: (int) $appointment->branch_id,
                userId: (int) $appointment->professional_id,
                sourceType: CommissionSourceCatalog::APPOINTMENT,
                sourceReference: (string) $appointment->id,
                revenueAmount: (float) $appointment->price,
                costAmount: null,
                quantity: 1,
                commissionRuleId: null,
                commissionTypeId: null,
                description: $appointment->title,
                metadata: [
                    'appointment_id' => $appointment->id,
                    'service_id' => $appointment->service_id,
                    'service_category_id' => $appointment->service?->service_category_id,
                    'status' => $event->statusSlug,
                ],
            ));

            return;
        }

        if (in_array($event->statusSlug, [AppointmentStatusCatalog::CANCELLED, AppointmentStatusCatalog::NO_SHOW], true)) {
            $commission = ProfessionalCommission::query()
                ->where('source_type', CommissionSourceCatalog::APPOINTMENT)
                ->where('source_reference', (string) $appointment->id)
                ->first();

            if ($commission instanceof ProfessionalCommission) {
                $this->reverseCommission->handle($event->actor, $commission, $event->reason ?? 'Appointment reversed.');
            }
        }
    }
}
