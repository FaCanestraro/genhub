<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':gallery,delete', only: ['destroy']),
        ];
    }

    public function destroy(Request $request, Asset $asset)
    {
        abort_if($asset->company_id !== $request->company()->id, 403);

        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();

        return response()->json(null, 204);
    }
}
