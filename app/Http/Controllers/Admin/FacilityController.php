<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Master\Agency;
use App\Models\Master\Facility;
use App\Traits\HasAutoPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FacilityController extends Controller implements HasMiddleware
{
    use HasAutoPermissions;

    /**
     * Tampilkan daftar fasilitas / faskes
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $agencyId = $request->query('agency_id');
        $type = $request->query('type');
        $erStatus = $request->query('er_status');

        $query = Facility::with(['agency'])->withCount('units');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($erStatus) {
            $query->where('er_status', $erStatus);
        }

        $facilities = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();

        return view('admin.facilities.index', compact('facilities', 'agencies'));
    }

    /**
     * Form tambah fasilitas baru
     */
    public function create(): View
    {
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        return view('admin.facilities.create', compact('agencies'));
    }

    /**
     * Simpan fasilitas baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:150',
            'agency_id'          => 'required|exists:agencies,id',
            'type'               => 'required|in:HOSPITAL,PUSKESMAS,FIRE_STATION,POLICE_STATION,OTHER',
            'address'            => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:50',
            'lat'                => 'nullable|numeric|between:-90,90',
            'lng'                => 'nullable|numeric|between:-180,180',
            'er_beds_total'      => 'nullable|integer|min:0',
            'er_beds_available'  => 'nullable|integer|min:0',
            'er_status'          => 'nullable|in:NORMAL,BUSY,FULL',
            'services'           => 'nullable|string', // comma separated or text
            'is_active'          => 'nullable|boolean',
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        // Parse services list
        $services = [];
        if (!empty($validated['services'])) {
            $services = array_values(array_filter(array_map('trim', explode(',', $validated['services']))));
        }

        $facilityData = [
            'name'              => $validated['name'],
            'agency_id'         => $validated['agency_id'],
            'type'              => $validated['type'],
            'address'           => $validated['address'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'lat'               => $lat,
            'lng'               => $lng,
            'er_beds_total'     => $validated['er_beds_total'] ?? 0,
            'er_beds_available' => $validated['er_beds_available'] ?? 0,
            'er_status'         => $validated['er_status'] ?? 'NORMAL',
            'er_updated_at'     => now(),
            'services'          => $services,
            'is_active'         => $request->boolean('is_active', true),
        ];

        if ($lat !== null && $lng !== null) {
            $facilityData['location'] = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)");
        }

        Facility::create($facilityData);

        return redirect()->route('facilities.index')
            ->with('success', 'Fasilitas / Pos Layanan berhasil ditambahkan!');
    }

    /**
     * Form edit fasilitas
     */
    public function edit(Facility $facility): View
    {
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        return view('admin.facilities.edit', compact('facility', 'agencies'));
    }

    /**
     * Update fasilitas
     */
    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:150',
            'agency_id'          => 'required|exists:agencies,id',
            'type'               => 'required|in:HOSPITAL,PUSKESMAS,FIRE_STATION,POLICE_STATION,OTHER',
            'address'            => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:50',
            'lat'                => 'nullable|numeric|between:-90,90',
            'lng'                => 'nullable|numeric|between:-180,180',
            'er_beds_total'      => 'nullable|integer|min:0',
            'er_beds_available'  => 'nullable|integer|min:0',
            'er_status'          => 'nullable|in:NORMAL,BUSY,FULL',
            'services'           => 'nullable|string',
            'is_active'          => 'nullable|boolean',
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        $services = [];
        if (!empty($validated['services'])) {
            $services = array_values(array_filter(array_map('trim', explode(',', $validated['services']))));
        }

        $facilityData = [
            'name'              => $validated['name'],
            'agency_id'         => $validated['agency_id'],
            'type'              => $validated['type'],
            'address'           => $validated['address'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'lat'               => $lat,
            'lng'               => $lng,
            'er_beds_total'     => $validated['er_beds_total'] ?? 0,
            'er_beds_available' => $validated['er_beds_available'] ?? 0,
            'er_status'         => $validated['er_status'] ?? 'NORMAL',
            'er_updated_at'     => now(),
            'services'          => $services,
            'is_active'         => $request->boolean('is_active', true),
        ];

        if ($lat !== null && $lng !== null) {
            $facilityData['location'] = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)");
        }

        $facility->update($facilityData);

        return redirect()->route('facilities.index')
            ->with('success', 'Data fasilitas berhasil diperbarui!');
    }

    /**
     * Hapus fasilitas
     */
    public function destroy(Facility $facility): RedirectResponse
    {
        // Cek relasi unit yang berbasis di fasilitas ini
        if ($facility->units()->exists()) {
            return redirect()->route('facilities.index')
                ->with('error', "Fasilitas {$facility->name} tidak dapat dihapus karena masih menjadi pangkalan armada unit aktif!");
        }

        $facility->delete();

        return redirect()->route('facilities.index')
            ->with('success', "Fasilitas {$facility->name} berhasil dihapus!");
    }
}
