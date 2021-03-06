<?php

namespace App\Http\Controllers;

use App\Dtos\response\Category\CategoryListDto;
use App\Dtos\response\Category\Partials\BasicCategoryDto;
use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class CategoriesController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $page_size = (int)$request->get('page_size', 10);

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $tags = Category::orderBy('created_at', 'desc')
            ->paginate($page_size);

        return $this->sendSuccess(CategoryListDto::build($tags, $request->path(), true));
    }

    public function store(Request $request)
    {
        $imagesCount = count($request->only('images')['images']);
        $imagesCount = min(0, $imagesCount - 1);
        $rules = ['name' => 'required|min:2|max:500',
            'description' => 'required|min:2|max:500',];

        /*
        foreach (range(0, min(6, $imagesCount)) as $index) {
            $rules['images.' . $index] = 'image|mimes:png|jpg|jpeg|size:3000';
        }
        */

        // validation
        $this->validate($request, $rules);

        $category = Category::create($request->only('name', 'description'));
        foreach ($request->images as $image) {
            $filepath = $image->store('/categories');
            CategoryImage::create([
                'category_id' => $category->id,
                'file_name' => explode('/', $filepath)[1],
                'file_path' => '/storage/' . $filepath,
                'original_name' => $image->getClientOriginalName()
            ]);
        }

        return $this->sendSuccess(BasicCategoryDto::build($category, true));
    }

}