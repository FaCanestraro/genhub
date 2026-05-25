<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function destroy(Request $request, Asset $asset)
    {
        abort_if($asset->user_id !== $request->user()->id, 403);

        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();

        return response()->json(null, 204);
    }
}
