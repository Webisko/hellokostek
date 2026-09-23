<?php

namespace Tests\Feature\Api;

use App\Models\GalleryArtwork;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryArtworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_artworks_index_returns_active_artworks_with_technique_and_format(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Olej',
            'slug' => 'olej',
            'is_active' => true,
        ]);

        $activeArtwork = GalleryArtwork::create([
            'category_id' => $category->id,
            'title' => 'Portret Psa',
            'technique' => 'Olej na płótnie owalnym',
            'format' => '30x40 cm',
            'year' => '2024',
            'image_path' => 'images/dog.webp',
            'original_url' => '/images/dog-scaled.webp',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $inactiveArtwork = GalleryArtwork::create([
            'category_id' => $category->id,
            'title' => 'Ukryty Obraz',
            'technique' => 'Rysunek',
            'format' => 'A4',
            'year' => '2020',
            'image_path' => 'images/hidden.webp',
            'sort_order' => 20,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/gallery');

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.title', 'Portret Psa')
            ->assertJsonPath('data.items.0.technique', 'Olej na płótnie owalnym')
            ->assertJsonPath('data.items.0.format', '30x40 cm')
            ->assertJsonPath('data.items.0.category', 'Olej')
            ->assertJsonPath('data.items.0.category_slug', 'olej');
    }
}
