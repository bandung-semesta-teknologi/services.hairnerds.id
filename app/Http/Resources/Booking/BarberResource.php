<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_user' => $this->id_user,
            'id_store' => $this->id_store,
            'calendar_accesstoken' => $this->calendar_accesstoken,
            'hairdnerds_calendarid' => $this->hairdnerds_calendarid,
            'primary_calendarid' => $this->primary_calendarid,
            'gender' => $this->gender,
            'characters' => $this->characters,
            'photo' => $this->photo,
            'email' => $this->email,
            'hashtag' => $this->hashtag,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'youtube_link' => $this->youtube_link,
            'full_name' => $this->full_name,
            'background_desc' => $this->background_desc,
            'phone' => $this->phone,
            'is_active' => (bool) $this->is_active,
            'color' => $this->color,
            'sync_status' => $this->sync_status,
            'total_review' => $this->total_review,
            'total_rating' => $this->total_rating,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
        ];
    }
}
