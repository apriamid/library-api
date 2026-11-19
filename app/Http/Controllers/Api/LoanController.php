<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    private const LOAN_DAYS = 7;

    //Loan return
    public function returnBook(Request $request, Loan $loan)
    {
        abort_unless($loan->user_id === $request->user()->id, 403, 'This loan is not yours.');

        DB::transaction(function () use ($loan) {
            abort_unless(
                Loan::whereKey($loan->id)->whereNull('returned_at')->update(['returned_at' => now()]),
                422, 'Book has already been returned.'
            );

            Book::whereKey($loan->book_id)->increment('stock');
        });

        return LoanResource::make($loan->refresh()->load('book.category'));
    }

    //Loan list
    public function index(Request $request)
    {
        $status = $request->query('status', 'active'); 
        $loans = $request->user()->loans()
            ->with('book.category')
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'returned', fn ($q) => $q->whereNotNull('returned_at'))
            ->latest('borrowed_at')
            ->paginate(15);

        return LoanResource::collection($loans);
    }

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
