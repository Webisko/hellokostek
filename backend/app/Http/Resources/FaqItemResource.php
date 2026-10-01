<?php

namespace App\Http\Resources;

use App\Models\FaqItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FaqItem
 */
class FaqItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'group_name' => $this->group_name,
            'sort_order' => $this->sort_order,
        ];
    }
}
