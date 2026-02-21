<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rdv;
use App\Models\Notification;
use App\Models\UINotification;
use Illuminate\Http\Request;

class RdvController extends Controller
{
    // GET /api/rdvs?from=...&to=...
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $from = $request->query('from');
        $to   = $request->query('to');

        $q = Rdv::with(['client', 'service', 'agent'])
            ->where('company_id', $companyId);

        $user = $request->user();
        if (($user->role ?? '') === 'agent') {
            $q->where('agent_id', $user->id);
        }

        if ($from) $q->where('start', '>=', $from);
        if ($to)   $q->where('end', '<=', $to);

        $list = $q->orderBy('start')->get();

        $events = $list->map(function ($rdv) {
            return [
                'id' => $rdv->id,
                'title' => $rdv->title ?: ($rdv->client?->full_name ?? 'RDV'),
                'start' => optional($rdv->start)->toISOString(),
                'end'   => optional($rdv->end)->toISOString(),
                'extendedProps' => [
                    'status' => $rdv->status,
                    'notes' => $rdv->notes,
                    'motifAnnulation' => $rdv->motif_annulation,
                    'clientId' => $rdv->client_id,
                    'serviceId' => $rdv->service_id,
                    'agentId' => $rdv->agent_id,
                    'client' => $rdv->client,
                    'service' => $rdv->service,
                    'agent' => $rdv->agent,
                ],
            ];
        });

        return response()->json($events);
    }

    // POST /api/rdvs
    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'client_id' => 'required|integer',
            'service_id' => 'required|integer',
            'agent_id' => 'required|integer',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
            'status' => 'required|string|in:en_attente,confirme,annule,reporte,realise,no_show',
            'notes' => 'nullable|string',
            'motif_annulation' => 'nullable|string',
        ]);

        $user = $request->user();

        // ✅ agent always forces his own id
        if (($user->role ?? '') === 'agent') {
            $data['agent_id'] = $user->id;
        }

        if (($data['status'] ?? '') === 'annule' && empty($data['motif_annulation'])) {
            return response()->json(['message' => "Motif d'annulation obligatoire"], 422);
        }

        $data['company_id'] = $companyId;

        $rdv = Rdv::create($data);
        $rdv->load(['client', 'service', 'agent']);

        $this->pushRdvNotif('rdv_created', $rdv);

        return response()->json($rdv, 201);
    }

    // PUT /api/rdvs/{id}
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $rdv = Rdv::with(['client', 'service', 'agent'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $user = $request->user();

        // ✅ permission BEFORE update
        if (($user->role ?? '') === 'agent' && (int)$rdv->agent_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'client_id' => 'sometimes|required|integer',
            'service_id' => 'sometimes|required|integer',
            'agent_id' => 'sometimes|required|integer',
            'start' => 'sometimes|required|date',
            'end' => 'sometimes|required|date|after:start',
            'status' => 'sometimes|required|string|in:en_attente,confirme,annule,reporte,realise,no_show',
            'notes' => 'nullable|string',
            'motif_annulation' => 'nullable|string',
        ]);

        // ✅ agent cannot change agent_id (even if sent)
        if (($user->role ?? '') === 'agent') {
            unset($data['agent_id']);
        }

        if (($data['status'] ?? $rdv->status) === 'annule') {
            $motif = $data['motif_annulation'] ?? $rdv->motif_annulation;
            if (!$motif) {
                return response()->json(['message' => "Motif d'annulation obligatoire"], 422);
            }
        }

        $rdv->update($data);
        $rdv->refresh()->load(['client', 'service', 'agent']);

        $this->pushRdvNotif('rdv_updated', $rdv);

        return response()->json($rdv);
    }

    // DELETE /api/rdvs/{id}
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $rdv = Rdv::with(['client', 'service', 'agent'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $user = $request->user();

        // ✅ permission BEFORE delete
        if (($user->role ?? '') === 'agent' && (int)$rdv->agent_id !== (int)$user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->pushRdvNotif('rdv_deleted', $rdv);

        $rdv->delete();

        return response()->json(['ok' => true]);
    }

    private function pushRdvNotif(string $eventType, Rdv $rdv): void
    {
        $companyId = $rdv->company_id;

        $client = $rdv->client;
        $service = $rdv->service;
        $agent = $rdv->agent;

        $dateTime = optional($rdv->start)->format('Y-m-d H:i');
        $clientName = $client?->full_name ?? 'Client';
        $serviceName = $service?->name ?? 'Service';
        $agentName = $agent?->name ?? ($agent?->email ?? 'Agent');
        $phone = $client?->phone;

        $titles = [
            'rdv_created' => 'Création RDV',
            'rdv_updated' => 'Modification RDV',
            'rdv_deleted' => 'Suppression RDV',
        ];

        $title = $titles[$eventType] ?? 'Notification';
        $body = "RDV: \"$serviceName\" ~ $clientName => $dateTime";

        UINotification::create([
            'company_id' => $companyId,
            'rdv_id' => $rdv->id,
            'type' => $eventType,
            'title' => $title,
            'body' => $body,
            'status' => 'unread',
            'action_url' => '/reservations',
        ]);

        if ($phone) {
            $prefix =
                $eventType === 'rdv_created' ? "✅ RDV créé\n" :
                ($eventType === 'rdv_updated' ? "✏️ RDV modifié\n" : "🗑️ RDV supprimé\n");

            $msg =
                $prefix .
                "Client: $clientName\n" .
                "Service: $serviceName\n" .
                "Agent: $agentName\n" .
                "Date: $dateTime\n";

            Notification::create([
                'company_id' => $companyId,
                'rdv_id' => $rdv->id,
                'type' => 'whatsapp',
                'status' => 'pending',
                'phone' => $phone,
                'message' => $msg,
                'tries' => 0,
            ]);
        }
    }

    public function storeRecurring(Request $request)
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'client_id' => 'required|integer',
            'service_id' => 'required|integer',
            'agent_id' => 'required|integer',
            'start' => 'required|date',
            'duration_min' => 'required|integer|min:5|max:1440',
            'status' => 'required|string|in:en_attente,confirme,annule,reporte,realise,no_show',
            'notes' => 'nullable|string',
            'motif_annulation' => 'nullable|string',
            'recurrence_freq' => 'required|string|in:weekly,monthly',
            'recurrence_interval' => 'required|integer|min:1|max:12',
            'recurrence_count' => 'required|integer|min:2|max:60',
        ]);

        $user = $request->user();

        // ✅ agent forces his own id
        if (($user->role ?? '') === 'agent') {
            $data['agent_id'] = $user->id;
        }

        if ($data['status'] === 'annule' && empty($data['motif_annulation'])) {
            return response()->json(['message' => "Motif d'annulation obligatoire"], 422);
        }

        $start = new \DateTime($data['start']);
        $duration = (int)$data['duration_min'];

        $seriesId = time();
        $created = 0;

        for ($i = 0; $i < (int)$data['recurrence_count']; $i++) {
            $s = clone $start;

            if ($i > 0) {
                if ($data['recurrence_freq'] === 'weekly') {
                    $s->modify('+' . (7 * $i * (int)$data['recurrence_interval']) . ' days');
                } else {
                    $s->modify('+' . ($i * (int)$data['recurrence_interval']) . ' months');
                }
            }

            $e = (clone $s)->modify('+' . $duration . ' minutes');

            Rdv::create([
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'service_id' => $data['service_id'],
                'agent_id' => $data['agent_id'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'motif_annulation' => $data['motif_annulation'] ?? null,
                'start' => $s->format('Y-m-d H:i:s'),
                'end'   => $e->format('Y-m-d H:i:s'),
                'series_id' => $seriesId,
                'recurrence_freq' => $data['recurrence_freq'],
                'recurrence_interval' => (int)$data['recurrence_interval'],
                'recurrence_count' => (int)$data['recurrence_count'],
                'occurrence_index' => $i,
            ]);

            $created++;
        }

        return response()->json(['created' => $created, 'series_id' => $seriesId], 201);
    }
}
