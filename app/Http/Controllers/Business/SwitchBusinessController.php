<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Chooses which of the user's businesses the business panel acts for (users can belong to several teams). */
class SwitchBusinessController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $id = (int) $request->validate(['business_id' => ['required', 'integer']])['business_id'];
        abort_unless($request->user()->businesses()->whereKey($id)->exists(), 403);
        $request->session()->put('current_business_id', $id);

        return redirect()->route($request->user()->homeRouteName());
    }
}
