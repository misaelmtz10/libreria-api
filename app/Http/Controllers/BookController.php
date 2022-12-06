<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReviews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{

    public function index()
    {
        //$books = Book::all(); //Book::with('authors'); //para traer solo los libros y los id de de category y editorial. NO TRAE AUTHORS
        $books = Book::with('authors', 'category', 'editorial')->get(); //para traer los libros con el authors
        /*return [
            "error" => false,
            "message" => "Successfull query",
            "data" => $books
        ];*/
        return $this->getResponse200($books);
    }

    public function store(Request $request)
    {

        DB::beginTransaction();
        try {

            //trim() -> Elimina espacio en blanco (u otro tipo de caracteres) del inicio y el final de la cadena
            $existIsbn = Book::where('isbn', trim($request->isbn))->exists();
            if (!$existIsbn) {
                $book = new Book();
                $book->isbn = trim($request->isbn);
                $book->title = $request->title;
                $book->description = $request->description;
                $book->publish_date = Carbon::now();
                $book->category_id = $request->category["id"];
                $book->editorial_id = $request->editorial_id;
                $book->save();

                foreach ($request->authors as $item) {
                    $book->authors()->attach($item);
                }
                $bookId = $book->id;
                /*return [
                    "status" => true,
                    "message" => "your book has been created",
                    "data" => [
                        "book_id" => $bookId,
                        "book" => $book
                    ]
                ];*/
                DB::commit(); //save all
                return $this->getResponse201('book', 'created', $book);
            } else {
                /* return [
                    "status" => false,
                    "message" => "The ISBN already exists",
                    "data" => []
                ];*/
                return $this->getResponse500(['The isbn field must be unique']);
            }
        } catch (Exception $e) {
            DB::rollBack(); //discard changes
            return $this->getResponse500([]);
        }
    }
    /*
    DATOS DE POSTMAN = http://localhost:8000/api/book/store

    {
"isbn": "013615250225",
"title": "register data with C-E-A",
"description": "Programming book",
"category":
    {
     "id":1
    },
"editorial_id": 1,
"authors":[
    {
        "id":2
    },
    {
        "id":4
    }
    ]
}

    */

    //UPDATE
    public function update(Request $request, $id)
    {

        //$response = $this->getResponse(); //mandar a llamar la función response
        DB::beginTransaction();
        try {
            $book = Book::find($id);
            if ($book) {
                $isbnOwner = Book::where("isbn", trim($request->isbn))->first(); //validar que el isb sea el mismo
                if (!$isbnOwner || $isbnOwner->id == $book->id) {
                    $book->isbn = trim($request->isbn);
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->publish_date = Carbon::now();
                    $book->category_id = $request->category["id"];
                    $book->editorial_id = $request->editorial_id;

                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item->id);
                    }
                    $book->update();
                    //ADD
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('category', 'editorial', 'authors')->where('id', $id)->get();


                    /*$response["error"] = false;
                    $request["message"] = "Your book has been updated!";
                    $response["data"] = $book;*/
                    DB::commit(); //save all
                    return $this->getResponse201('book', 'updated', $book);
                } else {
                    return $this->getResponseUnique();
                }
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            DB::rollBack(); //discard changes
            return $this->getResponse500($e);
        }

        //return $response;
    }

    public function show($id)
    {
        //$book = Book::with('authors', 'category', 'editorial')->where("id", $id)->get();
        $book = Book::with('authors', 'category', 'editorial')->find($id);
        //error_log($book);
        if ($book) {
            return $this->getResponse200($book);
        } else {
            return $this->getResponse404($book);
        }
    }

    public function delete($id)
    {

        DB::beginTransaction();
        try {

            //$response = $this->getResponse(); //mandar a llamar la función response
            $book = Book::with('authors', 'category', 'editorial')->find($id);

            if ($book) {
                //DELETE
                foreach ($book->authors as $item) {
                    $book->authors()->detach($item->id);
                }
                $book->delete();
                /*$response["error"] = false;
                $request["message"] = "your data has been deleted!";
                $response["data"] = $book;*/
                DB::commit();
                return $this->getResponseDelete200("book");
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            DB::rollBack(); //discard changes
            return $this->getResponse500([]);
        }
    }

    public function addBookReview(Request $request)
    {
        try {
            DB::beginTransaction();
            $review = new BookReviews();
            $review->comment = $request->comment;
            $review->book_id = $request->book_id;
            $review->user_id = auth()->user()->id;
            $review->save();
            DB::commit();
            return $this->getResponse201('review', 'created', $review);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }
    }

    public function updateBookReview(Request $request)
    {
        //return $request->review_id;
        DB::beginTransaction();
        try {
            $review = BookReviews::find($request->review_id);

            if ($review->user_id == auth()->user()->id) {
                $review->comment = $request->comment;
                $review->edited = true;
                $review->save();
                DB::commit();
                return $this->getResponse201('review', 'updated', $review);
            } else {
                return response()->json([
                    'message' => "You do not have permission to access this resource"
                ], 403);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }
    }
}
