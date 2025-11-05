<?php

use App\Models\User;
use App\Models\Book;
use App\Models\Review;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

beforeEach(function () {
    // Ensure we have a fresh database state for each test
    $this->artisan('migrate:fresh');
});

describe('User Model Relationships', function () {
    test('User has reviews method that returns HasMany relation', function () {
        $user = new User();

        expect(method_exists($user, 'reviews'))->toBeTrue()
            ->and($user->reviews())->toBeInstanceOf(HasMany::class);
    });

    test('User reviews relationship works correctly', function () {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        expect($user->reviews)
            ->toHaveCount(1)
            ->first()->id->toBe($review->id);
    });

    test('User can have multiple reviews', function () {
        $user = User::factory()->create();
        $books = Book::factory()->count(3)->create();

        foreach ($books as $book) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        expect($user->reviews)->toHaveCount(3);
    });
});

describe('Book Model Relationships', function () {
    test('Book has reviews method that returns HasMany relation', function () {
        $book = new Book();

        expect(method_exists($book, 'reviews'))->toBeTrue()
            ->and($book->reviews())->toBeInstanceOf(HasMany::class);
    });

    test('Book reviews relationship works correctly', function () {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        expect($book->reviews)
            ->toHaveCount(1)
            ->first()->id->toBe($review->id);
    });

    test('Book can have multiple reviews from different users', function () {
        $book = Book::factory()->create();
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            Review::factory()->create([
                'book_id' => $book->id,
                'user_id' => $user->id,
            ]);
        }

        expect($book->reviews)->toHaveCount(5);
    });

    test('Book has genres method that returns BelongsToMany relation', function () {
        $book = new Book();

        expect(method_exists($book, 'genres'))->toBeTrue()
            ->and($book->genres())->toBeInstanceOf(BelongsToMany::class);
    });

    test('Book genres relationship works correctly', function () {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        expect($book->genres)
            ->toHaveCount(1)
            ->first()->id->toBe($genre->id);
    });

    test('Book can have multiple genres', function () {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(3)->create();

        $book->genres()->attach($genres->pluck('id'));

        expect($book->genres)->toHaveCount(3);
    });

    test('Book genres uses correct pivot table', function () {
        $book = new Book();
        $relation = $book->genres();

        expect($relation->getTable())->toContain('books_genres');
    });
});

describe('Genre Model Relationships', function () {
    test('Genre has books method that returns BelongsToMany relation', function () {
        $genre = new Genre();

        expect(method_exists($genre, 'books'))->toBeTrue()
            ->and($genre->books())->toBeInstanceOf(BelongsToMany::class);
    });

    test('Genre books relationship works correctly', function () {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        expect($genre->books)
            ->toHaveCount(1)
            ->first()->id->toBe($book->id);
    });

    test('Genre can have multiple books', function () {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(4)->create();

        $genre->books()->attach($books->pluck('id'));

        expect($genre->books)->toHaveCount(4);
    });

    test('Genre books uses correct pivot table', function () {
        $genre = new Genre();
        $relation = $genre->books();

        expect($relation->getTable())->toContain('books_genres');
    });
});

describe('Review Model Relationships', function () {
    test('Review has user method that returns BelongsTo relation', function () {
        $review = new Review();

        expect(method_exists($review, 'user'))->toBeTrue()
            ->and($review->user())->toBeInstanceOf(BelongsTo::class);
    });

    test('Review user relationship works correctly', function () {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        expect($review->user)
            ->not->toBeNull()
            ->id->toBe($user->id);
    });

    test('Review has book method that returns BelongsTo relation', function () {
        $review = new Review();

        expect(method_exists($review, 'book'))->toBeTrue()
            ->and($review->book())->toBeInstanceOf(BelongsTo::class);
    });

    test('Review book relationship works correctly', function () {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        expect($review->book)
            ->not->toBeNull()
            ->id->toBe($book->id);
    });
});

describe('Complete Relationship Flow', function () {
    test('All relationships work together correctly', function () {
        // Create base models
        $user = User::factory()->create(['name' => 'Test User']);
        $book = Book::factory()->create(['title' => 'Test Book']);
        $genre = Genre::factory()->create(['name' => 'Test Genre']);

        // Link book and genre (many-to-many)
        $book->genres()->attach($genre->id);

        // Create review (user -> review, book -> review)
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'score' => 5,
        ]);

        // Test User -> Reviews
        expect($user->reviews)->toHaveCount(1)
            ->first()->score->toBe(5);

        // Test Book -> Reviews
        expect($book->reviews)->toHaveCount(1)
            ->first()->user_id->toBe($user->id);

        // Test Book <-> Genre
        expect($book->genres)->toHaveCount(1)
            ->first()->name->toBe('Test Genre');

        expect($genre->books)->toHaveCount(1)
            ->first()->title->toBe('Test Book');

        // Test Review -> User
        expect($review->user->name)->toBe('Test User');

        // Test Review -> Book
        expect($review->book->title)->toBe('Test Book');
    });

    test('Multiple users can review the same book', function () {
        $book = Book::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        expect($book->reviews)->toHaveCount(3);

        foreach ($users as $user) {
            expect($user->reviews)->toHaveCount(1);
        }
    });

    test('A book can have multiple genres and genres can have multiple books', function () {
        $books = Book::factory()->count(2)->create();
        $genres = Genre::factory()->count(2)->create();

        // First book has both genres
        $books[0]->genres()->attach($genres->pluck('id'));

        // Second book has only first genre
        $books[1]->genres()->attach($genres[0]->id);

        expect($books[0]->genres)->toHaveCount(2);
        expect($books[1]->genres)->toHaveCount(1);
        expect($genres[0]->books)->toHaveCount(2);
        expect($genres[1]->books)->toHaveCount(1);
    });
});
