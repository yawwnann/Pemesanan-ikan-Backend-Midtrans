<?php
// File: app/Http/Resources/UserResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name'); // Ambil nama roles saja
            }),
            'email_verified_at' => $this->email_verified_at?->toISOString(), // Format tanggal ISO

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}