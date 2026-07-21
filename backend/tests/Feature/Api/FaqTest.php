<?php

namespace Tests\Feature\Api;

use App\Models\FaqItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_index_returns_ok(): void
    {
        $response = $this->getJson('/api/faq');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['items'],
            ]);
    }

    public function test_faq_index_returns_empty_when_no_items(): void
    {
        $response = $this->getJson('/api/faq');

        $response->assertOk()
            ->assertJsonPath('data.items', []);
    }

    public function test_faq_index_returns_only_active_items(): void
    {
        FaqItem::factory()->count(2)->create(['is_active' => true]);
        FaqItem::factory()->inactive()->create();

        $response = $this->getJson('/api/faq');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_faq_items_are_ordered_by_sort_order(): void
    {
        FaqItem::factory()->create(['is_active' => true, 'sort_order' => 30, 'question' => 'Trzecie pytanie?']);
        FaqItem::factory()->create(['is_active' => true, 'sort_order' => 10, 'question' => 'Pierwsze pytanie?']);
        FaqItem::factory()->create(['is_active' => true, 'sort_order' => 20, 'question' => 'Drugie pytanie?']);

        $response = $this->getJson('/api/faq');

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertEquals('Pierwsze pytanie?', $items[0]['question']);
        $this->assertEquals('Drugie pytanie?', $items[1]['question']);
        $this->assertEquals('Trzecie pytanie?', $items[2]['question']);
    }

    public function test_faq_item_contains_expected_fields(): void
    {
        FaqItem::factory()->create([
            'is_active'  => true,
            'question'   => 'Testowe pytanie?',
            'answer'     => 'Testowa odpowiedź.',
            'group_name' => 'Zamówienia',
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/faq');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        ['id', 'question', 'answer', 'group_name', 'sort_order'],
                    ],
                ],
            ]);
    }
}
