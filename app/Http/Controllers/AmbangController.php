<?php

namespace App\Http\Controllers;

use App\Models\SensorThreshold;
use Illuminate\Http\Request;

class AmbangController extends Controller
{
    /** Jenis sensor yang dikenal firmware ESP32 — nama harus persis (dipakai /api/get-thresholds). */
    public const SENSOR = [
        'waterTemp' => 'Suhu Air (waterTemp)',
        'ph' => 'pH Air (ph)',
        'tds' => 'TDS (tds)',
        'airTemp' => 'Suhu Udara (airTemp)',
        'humidity' => 'Kelembaban (humidity)',
    ];

    public function index()
    {
        return view('ambang.index', ['ambangs' => SensorThreshold::all()]);
    }

    public function create()
    {
        $dipakai = SensorThreshold::pluck('sensor')->all();

        return view('ambang.create', ['daftar' => collect(self::SENSOR)->except($dipakai)]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        SensorThreshold::create($data);

        return redirect()->route('ambang.index')->with('success', 'Ambang sensor berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return view('ambang.edit', [
            'ambang' => SensorThreshold::findOrFail($id),
            'daftar' => self::SENSOR,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validasi($request, (int) $id);
        SensorThreshold::findOrFail($id)->update($data);

        return redirect()->route('ambang.index')->with('success', 'Ambang sensor berhasil diperbarui.');
    }

    public function destroy($id)
    {
        SensorThreshold::findOrFail($id)->delete();

        return redirect()->route('ambang.index')->with('success', 'Ambang sensor dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('ambang.index');
    }

    private function validasi(Request $request, ?int $kecualiId = null): array
    {
        $data = $request->validate([
            'sensor' => 'required|in:'.implode(',', array_keys(self::SENSOR))
                .'|unique:sensor_thresholds,sensor'.($kecualiId ? ','.$kecualiId : ''),
            'min' => 'required|numeric',
            'max' => 'required|numeric|gt:min',
        ], [
            'max.gt' => 'Nilai maksimum harus lebih besar daripada nilai minimum.',
        ]);

        return $data;
    }
}
