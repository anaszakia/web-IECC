<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Master\Agency;
use App\Models\Master\Facility;
use App\Models\Master\Unit;
use App\Models\Master\UnitMember;
use App\Models\User;
use App\Traits\HasAutoPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitController extends Controller implements HasMiddleware
{
    use HasAutoPermissions;

    /**
     * Tampilkan daftar unit
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $agencyId = $request->query('agency_id');
        $type = $request->query('type');
        $status = $request->query('status');

        $query = Unit::with(['agency', 'baseFacility', 'members.user'])
            ->withCount('members');

        if ($search) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $units = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();

        return view('admin.units.index', compact('units', 'agencies'));
    }

    /**
     * Form tambah unit baru
     */
    public function create(): View
    {
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        $facilities = Facility::where('is_active', true)->orderBy('name')->get();

        return view('admin.units.create', compact('agencies', 'facilities'));
    }

    /**
     * Simpan unit baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:20|unique:units,code',
            'agency_id'        => 'required|exists:agencies,id',
            'type'             => 'required|in:AMBULANCE,FIRE_TRUCK,POLICE_PATROL,RESCUE_TEAM,TRAFFIC_UNIT,OTHER',
            'status'           => 'required|in:AVAILABLE,BUSY,OFFLINE,MAINTENANCE',
            'crew_ready'       => 'nullable|boolean',
            'base_facility_id' => 'nullable|exists:facilities,id',
            'lat'              => 'nullable|numeric|between:-90,90',
            'lng'              => 'nullable|numeric|between:-180,180',
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        $unitData = [
            'code'             => strtoupper($validated['code']),
            'agency_id'        => $validated['agency_id'],
            'type'             => $validated['type'],
            'status'           => $validated['status'],
            'crew_ready'       => $request->boolean('crew_ready'),
            'base_facility_id' => $validated['base_facility_id'] ?? null,
            'lat'              => $lat,
            'lng'              => $lng,
        ];

        if ($lat !== null && $lng !== null) {
            $unitData['location'] = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)");
            $unitData['last_seen_at'] = now();
        }

        Unit::create($unitData);

        return redirect()->route('units.index')
            ->with('success', 'Unit armada berhasil ditambahkan!');
    }

    /**
     * Detail unit beserta anggota tim (crew)
     */
    public function show(Unit $unit): View
    {
        $unit->load([
            'agency',
            'baseFacility',
            'members.user',
            'assignments' => function ($q) {
                $q->with('incident')->latest()->limit(5);
            },
        ]);

        // Daftar calon anggota (petugas lapangan / field officers atau satu instansi)
        $existingUserIds = $unit->members->pluck('user_id')->toArray();
        $availableUsers = User::where('is_active', true)
            ->whereNotIn('id', $existingUserIds)
            ->where(function ($q) use ($unit) {
                $q->where('agency_id', $unit->agency_id)
                  ->orWhere('user_type', 'FIELD')
                  ->orWhereNull('agency_id');
            })
            ->orderBy('name')
            ->get();

        return view('admin.units.show', compact('unit', 'availableUsers'));
    }

    /**
     * Form edit unit
     */
    public function edit(Unit $unit): View
    {
        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        $facilities = Facility::where('is_active', true)->orderBy('name')->get();

        return view('admin.units.edit', compact('unit', 'agencies', 'facilities'));
    }

    /**
     * Update unit
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:20|unique:units,code,' . $unit->id,
            'agency_id'        => 'required|exists:agencies,id',
            'type'             => 'required|in:AMBULANCE,FIRE_TRUCK,POLICE_PATROL,RESCUE_TEAM,TRAFFIC_UNIT,OTHER',
            'status'           => 'required|in:AVAILABLE,BUSY,OFFLINE,MAINTENANCE',
            'crew_ready'       => 'nullable|boolean',
            'base_facility_id' => 'nullable|exists:facilities,id',
            'lat'              => 'nullable|numeric|between:-90,90',
            'lng'              => 'nullable|numeric|between:-180,180',
        ]);

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        $unitData = [
            'code'             => strtoupper($validated['code']),
            'agency_id'        => $validated['agency_id'],
            'type'             => $validated['type'],
            'status'           => $validated['status'],
            'crew_ready'       => $request->boolean('crew_ready'),
            'base_facility_id' => $validated['base_facility_id'] ?? null,
            'lat'              => $lat,
            'lng'              => $lng,
        ];

        if ($lat !== null && $lng !== null) {
            $unitData['location'] = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)");
            $unitData['last_seen_at'] = now();
        }

        $unit->update($unitData);

        return redirect()->route('units.index')
            ->with('success', 'Data unit berhasil diperbarui!');
    }

    /**
     * Hapus unit
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        // Cegah hapus jika unit sedang bertugas pada insiden aktif
        $activeAssignment = $unit->assignments()
            ->whereNotIn('status', ['RESOLVED', 'CANCELLED', 'REJECTED'])
            ->exists();

        if ($activeAssignment) {
            return redirect()->route('units.index')
                ->with('error', "Unit {$unit->code} sedang dalam tugas aktif dan tidak dapat dihapus!");
        }

        $unit->members()->delete();
        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', "Unit {$unit->code} berhasil dihapus!");
    }

    /**
     * Tambah Anggota (Crew) ke Unit
     */
    public function addMember(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|string|max:30',
            'on_duty' => 'nullable|boolean',
        ]);

        // Cek apakah user sudah terdaftar di unit ini
        if ($unit->members()->where('user_id', $validated['user_id'])->exists()) {
            return redirect()->back()->with('error', 'User sudah terdaftar sebagai anggota pada unit ini!');
        }

        $unit->members()->create([
            'user_id' => $validated['user_id'],
            'role'    => $validated['role'],
            'on_duty' => $request->boolean('on_duty', true),
        ]);

        // Jika ada anggota yang on_duty, pastikan update crew_ready jika diinginkan
        if ($request->boolean('on_duty')) {
            $unit->update(['crew_ready' => true]);
        }

        return redirect()->route('units.show', $unit)
            ->with('success', 'Anggota tim (crew) berhasil ditambahkan ke unit.');
    }

    /**
     * Update status / jabatan anggota unit
     */
    public function updateMember(Request $request, Unit $unit, UnitMember $member): RedirectResponse
    {
        $validated = $request->validate([
            'role'    => 'required|string|max:30',
            'on_duty' => 'nullable|boolean',
        ]);

        $member->update([
            'role'    => $validated['role'],
            'on_duty' => $request->boolean('on_duty'),
        ]);

        // Sinkronisasi status crew_ready unit berdasarkan keaktifan anggota
        $hasActiveMembers = $unit->members()->where('on_duty', true)->exists();
        $unit->update(['crew_ready' => $hasActiveMembers]);

        return redirect()->route('units.show', $unit)
            ->with('success', 'Status anggota tim berhasil diperbarui.');
    }

    /**
     * Hapus anggota dari unit
     */
    public function removeMember(Unit $unit, UnitMember $member): RedirectResponse
    {
        $member->delete();

        $hasActiveMembers = $unit->members()->where('on_duty', true)->exists();
        $unit->update(['crew_ready' => $hasActiveMembers]);

        return redirect()->route('units.show', $unit)
            ->with('success', 'Anggota berhasil dikeluarkan dari unit.');
    }
}
