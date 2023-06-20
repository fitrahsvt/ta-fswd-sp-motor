<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'brand')->get();
        $productforuser = Product::where('status', 'accepted')->get();
        // $products = Product::with('brands')->get();
        $brandcount = Brand::count();
        $categorycount = Category::count();

        return view('product.index', compact('products', 'brandcount', 'categorycount', 'productforuser'));
    }

    public function create()
    {
        $category = Category::all();
        $brand = Brand::all();
        return view('product.create', compact('category', 'brand'));
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'category' => 'required',
            'name' => 'required|string|min:3',
            'price' => 'required|integer',
            'brand' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,jfif|max:2048',
            'desc' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        // ubah nama file gambar dengan angka random
        $imageName = time().'.'.$request->image->extension();

        // upload file gambar ke folder slider
        Storage::putFileAs('public/product', $request->file('image'), $imageName);
        //masukkan data ke database
        $products = Product::create([
            'category_id' => $request->category,
            'name' => $request->name,
            'price' => $request->price,
            'brand_id' => $request->brand,
            'image' => $imageName,
            'desc' => $request->desc,
            'created_by' => $request->user,
        ]);

        // redirect ke halaman category.index
        return redirect()->route('product.index');
    }

    public function edit($id)
    {
        $product = Product::find($id);
        $category = Category::all();
        $brand = Brand::all();
        return view('product.edit', compact('category', 'brand', 'product'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(),[
            'category' => 'required',
            'name' => 'required|string|min:3',
            'price' => 'required|integer',
            'brand' => 'required',
            'desc' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        if ($request->hasFile('image')) {
            // ambil nama file gambar lama dari database
            $gambar_lama = Product::find($id)->image;

            //hapus file gambar lama
            Storage::delete('public/product/'.$gambar_lama);

            // ubah nama file gambar dengan angka random
            $imageName = time().'.'.$request->image->extension();

            // upload file gambar ke folder slider
            Storage::putFileAs('public/product', $request->file('image'), $imageName);

            //masukkan data ke database
            Product::where('id', $id)->update([
                'category_id' => $request->category,
                'name' => $request->name,
                'price' => $request->price,
                'brand_id' => $request->brand,
                'desc' => $request->desc,
                'image' => $imageName,
                'created_by' => 1,
            ]);
        }else{
            Product::where('id', $id)->update([
                'category_id' => $request->category,
                'name' => $request->name,
                'price' => $request->price,
                'brand_id' => $request->brand,
                'created_by' => 1,
                'desc' => $request->desc,
            ]);
        }
        return redirect()->route('product.index');
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        Storage::delete('public/product/'.$product->image);

        Product::where('id', $id)->delete();
        return redirect()->route('product.index');
    }

    public function show($id)
    {
        $product = Product::where('id', $id)->with('category')->first();

        $related = Product::where('status', 'accepted')->where('category_id', $product->category->id)->inRandomOrder()->limit(4)->get();

        if ($product) {
            return view('product.show', compact('product', 'related'));
        } else {
            abort(404);
        }

    }

    public function accepted($id)
    {
        Product::where('id', $id)->update([
            'status' => 'accepted',
        ]);

        return redirect()->route('product.index');
    }

    public function rejected($id)
    {
        Product::where('id', $id)->update([
            'status' => 'rejected',
        ]);

        return redirect()->route('product.index');
    }
}
