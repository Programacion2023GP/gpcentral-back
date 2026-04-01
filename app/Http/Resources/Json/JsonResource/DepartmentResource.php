<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
   public function toArray($request)
   {
      return [
         'id' => $this->id,
         'uuid' => $this->uuid,
         'organization' => new OrganizationResource($this->whenLoaded('organization')),
         'name' => $this->name,
         'seal_image' => $this->seal_image,
         'start_date' => $this->start_date->toDateString(),
         'end_date' => $this->end_date?->toDateString(),
         'active' => $this->active,
         'created_at' => $this->created_at,
         'updated_at' => $this->updated_at,
      ];
   }
}