<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The tile reference — every face in the set, drawn at the size the app uses.
 *
 * It is a workbench for building the artwork rather than a page for players, so
 * it is not served on the site the public reaches. A 404 rather than a redirect,
 * because to anyone outside the workshop the page simply is not there.
 */
class TileReferenceController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_if(app()->isProduction(), 404);

        return view('tiles', ['assign' => $request->boolean('assign')]);
    }
}
