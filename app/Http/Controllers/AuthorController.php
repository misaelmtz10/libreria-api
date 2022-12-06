<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthorController extends Controller
{
    public function index()
    {
        //$books = Book::all();
        $books = Author::with('books')->get(); //category hace referencia a la función del modelo
        return $this->getResponse200($books);
        /* return [
            "error" => false,
            "message" => 'Sí',
            "data" => $books
        ];*/
    }


    public function response()
    {
        return [
            "error" => true,
            "message" => "nO",
            "data" => []
        ];
    }

    public function store(Request $request)
    {
        $response = $this->response();
        DB::beginTransaction();
        try {

            $book = new Author();
            $book->name = $request->name;
            $book->first_name = $request->first_name;
            $book->second_surname = $request->second_surname;
            $book->save();
            foreach ($request->books as $item) {
                $book->books()->attach($item);
            }
            return $this->getResponse201("regitro", "c:", $book);



            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }

        return $response;
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        $response = $this->response();
        $book = Author::find($id);
        try {
            if ($book) {

                $book->name = $request->name;
                $book->first_name = $request->first_name;
                $book->second_surname = $request->second_surname;

                //$book->update();
                foreach ($book->books as $item) {
                    $book->books()->detach($item->id);
                }
                foreach ($request->books as $item) {
                    $book->books()->attach($item);
                }
                $book->update();
                return $this->getResponse201(":3", "c:", $book);
            } else {
                return $this->getResponse404();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
        return $response;
    }

    public function destroy($id)
    {
        $book = Author::find($id);
        if ($book) {
            foreach ($book->books as $item) {
                $book->books()->detach($item->id);
            }
            $book->delete();
            return $this->getResponseDelete200($id);
        } else {
            return $this->getResponse404();
        }
    }


    public function show($id)
    {
        $book = Author::find($id);
        if ($book) {

            return $this->getResponse200($book);
        } else {
            return $this->getResponse404();
        }
    }






}
