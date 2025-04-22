<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'legislative_district' => $this->underMunicipality ? true : false,
        ];

        if ($this->underMunicipality) {
            $data['belongs_to'] = $this->underMunicipality->name;
        } else {
            $data['municipalities'] = MunicipalityResource::collection(
                $this->whenLoaded('municipality') // many-to-many
            );
        }

        return $data;
    }
}
