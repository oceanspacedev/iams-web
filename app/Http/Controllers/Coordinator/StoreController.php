<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Store::withCount('audits')->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('business_entity', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $stores = $query->get()->map(fn ($s) => [
            'id'              => $s->id,
            'code'            => $s->code,
            'name'            => $s->name,
            'business_entity' => $s->business_entity ?: '-',
            'type'            => $s->type ?: 'toko',
            'area'            => $s->area ?: '-',
            'regional'        => $s->regional ?: '-',
            'status'          => $s->status,
            'audits_count'    => $s->audits_count,
        ]);

        return Inertia::render('Coordinator/Stores/Index', [
            'stores' => $stores,
            'filters' => [
                'search' => $request->query('search', ''),
                'type'   => $request->query('type', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Coordinator/Stores/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:20|unique:stores,code',
            'name'            => 'required|string|max:255',
            'business_entity' => 'nullable|string|max:100',
            'type'            => 'required|in:toko,gudang,head_office,hub',
            'area'            => 'nullable|string|max:100',
            'regional'        => 'nullable|string|max:100',
            'status'          => 'required|in:active,inactive',
        ]);

        Store::create($validated);

        return redirect()->route('coordinator.stores.index')->with('success', 'Data saved! Unit / Toko / Gudang berhasil ditambahkan.');
    }

    public function edit(Store $store): Response
    {
        return Inertia::render('Coordinator/Stores/Edit', [
            'store' => [
                'id'              => $store->id,
                'code'            => $store->code,
                'name'            => $store->name,
                'business_entity' => $store->business_entity,
                'type'            => $store->type ?: 'toko',
                'area'            => $store->area,
                'regional'        => $store->regional,
                'status'          => $store->status,
            ],
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:20|unique:stores,code,' . $store->id,
            'name'            => 'required|string|max:255',
            'business_entity' => 'nullable|string|max:100',
            'type'            => 'required|in:toko,gudang,head_office,hub',
            'area'            => 'nullable|string|max:100',
            'regional'        => 'nullable|string|max:100',
            'status'          => 'required|in:active,inactive',
        ]);

        $store->update($validated);

        return redirect()->route('coordinator.stores.index')->with('success', 'Data saved! Data unit toko/gudang berhasil diperbarui.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        if ($store->audits()->exists()) {
            return back()->with('error', 'Toko tidak dapat dihapus karena memiliki riwayat audit.');
        }

        $store->delete();

        return redirect()->route('coordinator.stores.index')->with('success', 'Toko / Gudang berhasil dihapus.');
    }

    /**
     * Import Stores & Warehouses from CSA CSV format
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Gagal membaca file CSV.');
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong.');
        }

        // Clean headers
        $header = array_map(fn ($h) => strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h))), $header);

        $inserted = 0;
        $updated = 0;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 2) continue;

            $data = array_combine(array_slice($header, 0, count($row)), array_slice($row, 0, count($header)));

            $code           = trim($data['code'] ?? $data['kode'] ?? $data['kode_toko'] ?? $data['kode_unit'] ?? '');
            $name           = trim($data['name'] ?? $data['nama'] ?? $data['nama_toko'] ?? $data['nama_unit'] ?? '');
            $businessEntity = trim($data['business_entity'] ?? $data['badan_usaha'] ?? $data['pt'] ?? '');
            $type           = strtolower(trim($data['type'] ?? $data['tipe'] ?? $data['jenis'] ?? 'toko'));
            $area           = trim($data['area'] ?? $data['wilayah'] ?? '');
            $regional       = trim($data['regional'] ?? $data['region'] ?? '');
            $status         = strtolower(trim($data['status'] ?? 'active'));

            if (empty($code) || empty($name)) continue;

            if (!in_array($type, ['toko', 'gudang', 'head_office', 'hub'])) {
                $type = 'toko';
            }
            if (!in_array($status, ['active', 'inactive'])) {
                $status = 'active';
            }

            $store = Store::updateOrCreate(
                ['code' => $code],
                [
                    'name'            => $name,
                    'business_entity' => $businessEntity ?: null,
                    'type'            => $type,
                    'area'            => $area ?: null,
                    'regional'        => $regional ?: null,
                    'status'          => $status,
                ]
            );

            if ($store->wasRecentlyCreated) {
                $inserted++;
            } else {
                $updated++;
            }
        }

        fclose($handle);

        return redirect()->route('coordinator.stores.index')
            ->with('success', "Data saved! Import CSA berhasil. {$inserted} data baru ditambahkan, {$updated} data diperbarui.");
    }

    /**
     * Download sample CSV template matching CSA format
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Template_Import_CSA_Toko_Gudang.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Header row
            fputcsv($handle, ['code', 'name', 'business_entity', 'type', 'area', 'regional', 'status']);
            // Sample rows
            fputcsv($handle, ['STR-JKT-01', 'Toko Central Grand Mall', 'PT. Sumber Ritel Sejahtera', 'toko', 'Jakarta Pusat', 'Regional 1', 'active']);
            fputcsv($handle, ['GDG-CKR-01', 'Gudang Distribusi Cikarang', 'PT. Logistik Prima Solusi', 'gudang', 'Bekasi', 'Regional 1', 'active']);
            fputcsv($handle, ['STR-BDG-02', 'Toko Dago Heritage', 'PT. Sumber Ritel Sejahtera', 'toko', 'Bandung', 'Regional 2', 'active']);
            fputcsv($handle, ['HO-JKT-00', 'Head Office Sudirman', 'PT. Sumber Ritel Sejahtera', 'head_office', 'Jakarta Selatan', 'Regional 1', 'active']);
            fclose($handle);
        }, 200, $headers);
    }
}
