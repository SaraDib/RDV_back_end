<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Rdv;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        return Client::where('company_id', $request->user()->company_id)
            ->orderBy('full_name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $data['company_id'] = $request->user()->company_id;

        $client = Client::create($data);

        return response()->json($client, 201);
    }

    public function update(Request $request, $id)
    {
        $client = Client::where('company_id', $request->user()->company_id)->findOrFail($id);

        $data = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Request $request, $id)
    {
        $client = Client::where('company_id', $request->user()->company_id)->findOrFail($id);
        $client->delete();

        return response()->json(['ok' => true]);
    }

    // ✅ FICHE + HISTORIQUE
    public function history(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $client = Client::where('company_id', $companyId)->findOrFail($id);

        $rdvs = Rdv::where('company_id', $companyId)
            ->where('client_id', $client->id)
            ->orderByDesc('start')
            ->get();

        return response()->json([
            'client' => $client,
            'rdvs' => $rdvs,
        ]);
    }

    // ✅ EXPORT CSV
    public function export(Request $request)
    {
        $companyId = $request->user()->company_id;

        $clients = Client::where('company_id', $companyId)->orderBy('full_name')->get();

        $response = new StreamedResponse(function () use ($clients) {
            $handle = fopen('php://output', 'w');

            // header CSV
            fputcsv($handle, ['full_name', 'email', 'phone']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->full_name,
                    $client->email,
                    $client->phone,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="clients.csv"');

        return $response;
    }

    // ✅ IMPORT CSV
    // expected columns: full_name,email,phone (header optional)
    public function import(Request $request)
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return response()->json(['message' => 'Impossible de lire le fichier'], 422);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        // read first row
        $first = fgetcsv($handle);
        if ($first === false) {
            fclose($handle);
            return response()->json(['message' => 'Fichier vide'], 422);
        }

        // detect header
        $lower = array_map(fn($x) => strtolower(trim((string)$x)), $first);
        $hasHeader = in_array('full_name', $lower) || in_array('nom', $lower);

        // if header -> use as columns map, else treat as first data row
        $columns = $hasHeader ? $lower : ['full_name', 'email', 'phone'];

        // helper to process one row
        $processRow = function(array $row) use ($columns, $companyId, &$created, &$updated, &$skipped) {
            $row = array_map(fn($x) => trim((string)$x), $row);
            if (count($row) === 0) { $skipped++; return; }

            $data = [];
            foreach ($columns as $i => $col) {
                if (!isset($row[$i])) continue;
                $data[$col] = $row[$i];
            }

            $fullName = $data['full_name'] ?? ($data['nom'] ?? null);
            $email = $data['email'] ?? null;
            $phone = $data['phone'] ?? ($data['telephone'] ?? null);

            if (!$fullName) { $skipped++; return; }

            // choose unique key: email if exists else phone else full_name
            $query = Client::where('company_id', $companyId);

            if ($email) $query->where('email', $email);
            elseif ($phone) $query->where('phone', $phone);
            else $query->where('full_name', $fullName);

            $existing = $query->first();

            if ($existing) {
                $existing->update([
                    'full_name' => $fullName,
                    'email' => $email ?: $existing->email,
                    'phone' => $phone ?: $existing->phone,
                ]);
                $updated++;
            } else {
                Client::create([
                    'company_id' => $companyId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                ]);
                $created++;
            }
        };

        // if first row is data (no header) => process it
        if (!$hasHeader) {
            $processRow($first);
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && trim((string)$row[0]) === '') continue;
            $processRow($row);
        }

        fclose($handle);

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }
}
