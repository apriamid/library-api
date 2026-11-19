<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    private const LOAN_DAYS = 7;

    public function store(StoreLoanRequest $request)
    {
        $user   = $request->user();
        $bookId = $request->validated('book_id');

        $loan = DB::transaction(function () use ($user, $bookId) {
            abort_if(
                $user->loans()->active()->where('book_id', $bookId)->exists(),
                422, 'You have already borrowed this book.'
            );

            // Atomik: hanya berhasil jika stok > 0, aman dari race condition.
            abort_unless(
                Book::whereKey($bookId)->where('stock', '>', 0)->decrement('stock'),
                422, 'Book is out of stock.'
            );

            return $user->loans()->create([
                'book_id'     => $bookId,
                'borrowed_at' => now(),
                'due_at'      => now()->addDays(self::LOAN_DAYS),
            ]);
        });

        return LoanResource ::make($loan->load('book.category'))->response()->setStatusCode(201);
    }
}
