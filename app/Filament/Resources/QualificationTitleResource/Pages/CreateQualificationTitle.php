<?php
namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use App\Models\QualificationTitle;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateQualificationTitle extends CreateRecord
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): QualificationTitle
    {
        return DB::transaction(function () use ($data) {
            $costing = [
                $data['training_cost_pcc'] ?? 0,
                $data['cost_of_toolkit_pcc'] ?? 0,
                $data['training_support_fund'] ?? 0,
                $data['assessment_fee'] ?? 0,
                $data['entrepeneurship_fee'] ?? 0,
                $data['new_normal_assisstance'] ?? 0,
                $data['accident_insurance'] ?? 0,
                $data['book_allowance'] ?? 0,
                $data['uniform_allowance'] ?? 0,
                $data['misc_fee'] ?? 0
            ];

            $total_pcc = $this->computePCC($costing);

            $target = QualificationTitle::create([
                'training_program_id' => $data['training_program_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'training_cost_pcc' => $data['training_cost_pcc'],
                'cost_of_toolkit_pcc' => $data['cost_of_toolkit_pcc'],
                'training_support_fund' => $data['training_support_fund'],
                'assessment_fee' => $data['assessment_fee'],
                'entrepeneurship_fee' => $data['entrepeneurship_fee'],
                'new_normal_assisstance' => $data['new_normal_assisstance'],
                'accident_insurance' => $data['accident_insurance'],
                'book_allowance' => $data['book_allowance'],
                'uniform_allowance' => $data['uniform_allowance'],
                'misc_fee' => $data['misc_fee'],
                'hours_duration' => $data['hours_duration'],
                'days_duration' => $data['days_duration'],
                'pcc' => $total_pcc
            ]);

            return $target;
        });
    }

    protected function computePCC(array $costing): float
    {
        $total_pcc = 0;

        foreach ($costing as $value) {
            $total_pcc += $value;
        }

        return $total_pcc;
    }
}
