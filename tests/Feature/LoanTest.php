<?php

namespace Tests\Feature;

use App\Models\{Book, Category, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(int $stock = 1): Book
    {
        $category = Category::firstOrCreate(['name' => 'Fiction']);

        return Book::create([
            'category_id' => $category->id, 'title' => 'T', 'author' => 'A',
            'isbn' => uniqid(), 'stock' => $stock,
        ]);
    }

    public function test_borrow_list_and_return_flow(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $book = $this->makeBook();

        $loanId = $this->postJson('/api/loans', ['book_id' => $book->id])
            ->assertCreated()->json('data.id');
        $this->assertSame(0, $book->fresh()->stock);

        $this->getJson('/api/loans')->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/loans/{$loanId}/return")->assertOk();
        $this->assertSame(1, $book->fresh()->stock);

        $this->getJson('/api/loans')->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson("/api/loans/{$loanId}/return")->assertStatus(422);
    }

    public function test_cannot_borrow_out_of_stock_book(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/loans', ['book_id' => $this->makeBook(0)->id])->assertStatus(422);
    }

    public function test_cannot_return_other_users_loan(): void
    {
        $book = $this->makeBook();
        Sanctum::actingAs(User::factory()->create());
        $loanId = $this->postJson('/api/loans', ['book_id' => $book->id])->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->patchJson("/api/loans/{$loanId}/return")->assertForbidden();
    }
}