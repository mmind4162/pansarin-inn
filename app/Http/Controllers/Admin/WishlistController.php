<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Admin\WishlistRepository;
use App\Http\Requests\Admin\WishlistRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WishlistController extends Controller
{
    protected $wishlistRepository;

    public function __construct(WishlistRepository $wishlistRepository)
    {
        $this->wishlistRepository = $wishlistRepository;
    }

    public function index()
    {
        return Inertia::render('Admin/Wishlist/Index', [
            'stats' => $this->wishlistRepository->getStats(),
        ]);
    }

    public function getData(Request $request)
    {
        try {
            return $this->wishlistRepository->getAllForDataTable($request);
        } catch (\Exception $e) {
            Log::error('Wishlist getData error: '.$e->getMessage());
            return response()->json(['error' => 'Failed to load data', 'data' => [], 'total' => 0], 500);
        }
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'thumbnail'])->map(function ($p) {
            $path = $p->thumbnail ? public_path('storage/'.$p->thumbnail) : null;
            return [
                'id'    => $p->id,
                'name'  => $p->name,
                'image' => ($path && file_exists($path)) ? asset('storage/'.$p->thumbnail) : null,
            ];
        })->values();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Wishlist/Create', [
            'products' => $products,
            'users'    => $users,
        ]);
    }

    public function store(WishlistRequest $request)
    {
        try {
            $this->wishlistRepository->store($request->validated());
            return to_route('admin.wishlist.index')->with('success', 'Wishlist entry created successfully!');
        } catch (\Exception $e) {
            Log::error('Wishlist store error: '.$e->getMessage());
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        return Inertia::render('Admin/Wishlist/Show', [
            'wishlist' => $this->wishlistRepository->find($id),
        ]);
    }

    public function destroy($id)
    {
        try {
            $this->wishlistRepository->delete($id);
            return to_route('admin.wishlist.index')->with('success', 'Wishlist entry deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Wishlist delete error: '.$e->getMessage());
            return back()->with('error', 'Failed to delete wishlist entry.');
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:wishlists,id']);
            $count = $this->wishlistRepository->bulkDelete($request->ids);
            return back()->with('success', $count.' wishlist entries deleted!');
        } catch (\Exception $e) {
            Log::error('Wishlist bulk delete error: '.$e->getMessage());
            return back()->with('error', 'Failed to delete wishlist entries.');
        }
    }
}
