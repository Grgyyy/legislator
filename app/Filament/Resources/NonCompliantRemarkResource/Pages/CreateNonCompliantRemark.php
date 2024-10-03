<?php

namespace App\Filament\Resources\NonCompliantRemarkResource\Pages;

use App\Filament\Resources\NonCompliantRemarkResource;
use App\Models\NonCompliantRemark;
use App\Models\Target;
use App\Models\TargetStatus;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNonCompliantRemark extends CreateRecord
{
    protected static string $resource = NonCompliantRemarkResource::class;

    protected static ?string $title = 'Non-Compliant Targets';

    public function getBreadcrumbs(): array
    {
        return [
            'Non-Compliant Targets',
            'List'
        ];
    }

    protected function handleRecordCreation(array $data): NonCompliantRemark
    {
        return DB::transaction(function () use ($data) {
            $targetId = (int) $data['target_id'];

            $nonCompliantRecord = TargetStatus::where('desc', 'Non-Compliant')->first();

            $target = Target::find($targetId);
            if (!$target) {
                throw new \Exception('Target Record not found');
            }

            $nonCompliantRemark = NonCompliantRemark::create([
                'target_remarks_id' => $data['target_remarks_id'],
                'others_remarks' => $data['others_remarks'],
                'target_id' => $targetId,
            ]);

            if ($nonCompliantRecord) {
                $target->target_status_id = $nonCompliantRecord->id;
                $target->save();
            }

            return $nonCompliantRemark;
        });
    }
}
