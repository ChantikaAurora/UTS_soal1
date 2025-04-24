<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdateMovieRequest;

class MovieController extends Controller
{

    public function index()
    {

        $query = Movie::latest();
        if (request('search')) {
            $query->where('judul', 'like', '%' . request('search') . '%')
                ->orWhere('sinopsis', 'like', '%' . request('search') . '%');
        }
        $movies = $query->paginate(6)->withQueryString();
        return view('homepage', compact('movies'));
    }

    public function detail($id)
    {
        $movie = Movie::find($id);
        return view('detail', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('input', compact('categories'));
    }

    public function store(StoreMovieRequest $request)
    {
        // Handle file upload
        $fileName = $this->uploadCoverImage($request);

        // Simpan data ke database
        Movie::create([
            'id' => $request->id,
            'judul' => $request->judul,
            'category_id' => $request->category_id,
            'sinopsis' => $request->sinopsis,
            'tahun' => $request->tahun,
            'pemain' => $request->pemain,
            'foto_sampul' => $fileName,
        ]);

        return redirect('/')->with('success', 'Data berhasil disimpan');
    }

    private function uploadCoverImage(Request $request): string
    {
        $randomName = Str::uuid()->toString();
        $fileExtension = 'jpg'; // bisa juga pakai getClientOriginalExtension()
        $fileName = $randomName . '.' . $fileExtension;

        $request->file('foto_sampul')->move(public_path('images'), $fileName);

        return $fileName;
    }

    public function data()
    {
        $movies = Movie::latest()->paginate(10);
        return view('data-movies', compact('movies'));
    }

    public function form_edit($id)
    {
        $movie = Movie::find($id);
        $categories = Category::all();
        return view('form-edit', compact('movie', 'categories'));
    }

    public function update(UpdateMovieRequest $request, $id)
    {
        $movie = Movie::findOrFail($id);
    
        // Jika ada file baru, upload & hapus foto lama
        if ($request->hasFile('foto_sampul')) {
            // Hapus foto lama
            $this->deleteCoverImage($movie->foto_sampul);
    
            $fileName = $this->uploadCoverImage($request);
            $movie->foto_sampul = $fileName;
        }
    
        // Update data lainnya
        $movie->judul = $request->judul;
        $movie->category_id = $request->category_id;
        $movie->sinopsis = $request->sinopsis;
        $movie->tahun = $request->tahun;
        $movie->pemain = $request->pemain;
    
        $movie->save();
    
        return redirect('/movies/data')->with('success', 'Data berhasil diperbarui');
    }

    public function delete($id)
    {
        $movie = Movie::findOrFail($id);

        // Delete the movie's photo if it exists
        $this->deleteCoverImage($movie->foto_sampul);

        // Delete the movie record from the database
        $movie->delete();

        return redirect('/movies/data')->with('success', 'Data berhasil dihapus');
    }

    private function deleteCoverImage(string $filename): void
    {
        $path = public_path('images/' . $filename);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
