<?php
namespace App\Http\Controllers;
use App\Models\WaterControl;
use Illuminate\Http\Request;

class WaterControlController extends Controller
{
    public function index()
    {
        return view('watercontrol.index', ['wc' => WaterControl::current()]);
    }

    public function fill()
    {
        WaterControl::query()->update(['command' => 'FORCE_FILL']);
        return back()->with('success', 'Perintah isi air dikirim.');
    }

    public function stop()
    {
        WaterControl::query()->update(['command' => 'FORCE_STOP']);
        return back()->with('success', 'Pengisian dihentikan.');
    }

    public function status()
    {
        return response()->json(WaterControl::current());
    }

    public function setMode(Request $request)
    {
        $request->validate(['mode' => 'required|in:AUTO,MANUAL']);
        WaterControl::query()->update(['mode' => $request->mode]);
        return response()->json(['status' => true]);
    }
}
