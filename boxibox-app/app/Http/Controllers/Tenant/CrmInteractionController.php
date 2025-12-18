<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CrmInteraction;
use App\Models\CrmInteractionAttachment;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CrmInteractionController extends Controller
{
    /**
     * Get timeline for a lead
     */
    public function leadTimeline(Request $request, Lead $lead): Response
    {
        $this->authorize('view', $lead);

        $timeline = $this->getTimelineData($lead, $request);

        return Inertia::render('Tenant/CRM/Leads/Timeline', [
            'entity' => $this->formatEntityData($lead, 'lead'),
            'timeline' => $timeline['items'],
            'stats' => $timeline['stats'],
            'filters' => $timeline['filters'],
            'users' => User::where('tenant_id', $request->user()->tenant_id)
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Get timeline for a customer
     */
    public function customerTimeline(Request $request, Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $timeline = $this->getTimelineData($customer, $request);

        return Inertia::render('Tenant/CRM/Customers/Timeline', [
            'entity' => $this->formatEntityData($customer, 'customer'),
            'timeline' => $timeline['items'],
            'stats' => $timeline['stats'],
            'filters' => $timeline['filters'],
            'users' => User::where('tenant_id', $request->user()->tenant_id)
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Get timeline data as JSON (for AJAX refresh)
     */
    public function getTimeline(Request $request): JsonResponse
    {
        $entityType = $request->input('entity_type');
        $entityId = $request->input('entity_id');

        $entity = match ($entityType) {
            'lead' => Lead::findOrFail($entityId),
            'customer' => Customer::findOrFail($entityId),
            'prospect' => Prospect::findOrFail($entityId),
            default => abort(400, 'Invalid entity type'),
        };

        $timeline = $this->getTimelineData($entity, $request);

        return response()->json($timeline);
    }

    /**
     * Store a new interaction
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,customer,prospect',
            'entity_id' => 'required|integer',
            'type' => 'required|in:call,email,email_received,meeting,visit,sms,sms_received,note,task,reminder,whatsapp,chat,other',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'outcome' => 'nullable|string|max:500',
            'direction' => 'nullable|in:inbound,outbound',
            'duration_seconds' => 'nullable|integer|min:0',
            'scheduled_at' => 'nullable|date',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'sentiment' => 'nullable|in:positive,neutral,negative',
            'reminder_at' => 'nullable|date',
            'related_contract_id' => 'nullable|exists:contracts,id',
            'related_invoice_id' => 'nullable|exists:invoices,id',
            'related_quote_id' => 'nullable|exists:quotes,id',
            'metadata' => 'nullable|array',
        ]);

        // Get the entity
        $entityClass = match ($validated['entity_type']) {
            'lead' => Lead::class,
            'customer' => Customer::class,
            'prospect' => Prospect::class,
        };
        $entity = $entityClass::findOrFail($validated['entity_id']);

        // Create interaction
        $interaction = CrmInteraction::create([
            'tenant_id' => $request->user()->tenant_id,
            'interactable_type' => $entityClass,
            'interactable_id' => $entity->id,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'sentiment' => $validated['sentiment'] ?? null,
            'reminder_at' => $validated['reminder_at'] ?? null,
            'related_contract_id' => $validated['related_contract_id'] ?? null,
            'related_invoice_id' => $validated['related_invoice_id'] ?? null,
            'related_quote_id' => $validated['related_quote_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        // Update last_activity_at on entity
        if (method_exists($entity, 'update')) {
            $entity->update(['last_activity_at' => now()]);

            // If it's a contact type, update last_contacted_at
            if (in_array($validated['type'], ['call', 'email', 'sms', 'meeting', 'visit', 'whatsapp'])) {
                $entity->update(['last_contacted_at' => now()]);
            }
        }

        $interaction->load(['user', 'attachments']);

        return response()->json([
            'success' => true,
            'interaction' => $this->formatInteraction($interaction),
            'message' => 'Interaction ajoutée avec succès',
        ]);
    }

    /**
     * Update an interaction
     */
    public function update(Request $request, CrmInteraction $interaction): JsonResponse
    {
        // Ensure the interaction belongs to the user's tenant
        if ($interaction->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:call,email,email_received,meeting,visit,sms,sms_received,note,task,reminder,whatsapp,chat,other',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'outcome' => 'nullable|string|max:500',
            'direction' => 'nullable|in:inbound,outbound',
            'duration_seconds' => 'nullable|integer|min:0',
            'scheduled_at' => 'nullable|date',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'sentiment' => 'nullable|in:positive,neutral,negative',
            'is_completed' => 'sometimes|boolean',
            'reminder_at' => 'nullable|date',
        ]);

        // Handle completion
        if (isset($validated['is_completed']) && $validated['is_completed'] && !$interaction->is_completed) {
            $validated['completed_at'] = now();
        }

        $interaction->update($validated);
        $interaction->load(['user', 'attachments']);

        return response()->json([
            'success' => true,
            'interaction' => $this->formatInteraction($interaction),
            'message' => 'Interaction mise à jour',
        ]);
    }

    /**
     * Delete an interaction
     */
    public function destroy(Request $request, CrmInteraction $interaction): JsonResponse
    {
        if ($interaction->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Delete attachments
        foreach ($interaction->attachments as $attachment) {
            Storage::delete($attachment->path);
            $attachment->delete();
        }

        $interaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Interaction supprimée',
        ]);
    }

    /**
     * Mark interaction as completed
     */
    public function complete(Request $request, CrmInteraction $interaction): JsonResponse
    {
        if ($interaction->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'outcome' => 'nullable|string|max:500',
        ]);

        $interaction->update([
            'is_completed' => true,
            'completed_at' => now(),
            'outcome' => $validated['outcome'] ?? $interaction->outcome,
        ]);

        return response()->json([
            'success' => true,
            'interaction' => $this->formatInteraction($interaction->fresh(['user', 'attachments'])),
            'message' => 'Interaction marquée comme terminée',
        ]);
    }

    /**
     * Upload attachment to interaction
     */
    public function uploadAttachment(Request $request, CrmInteraction $interaction): JsonResponse
    {
        if ($interaction->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store("crm-attachments/{$interaction->tenant_id}", 'public');

        $attachment = CrmInteractionAttachment::create([
            'crm_interaction_id' => $interaction->id,
            'filename' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'attachment' => $attachment,
        ]);
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(Request $request, CrmInteractionAttachment $attachment): JsonResponse
    {
        $interaction = $attachment->interaction;

        if ($interaction->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pièce jointe supprimée',
        ]);
    }

    /**
     * Get upcoming tasks/meetings
     */
    public function upcoming(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $days = $request->input('days', 7);

        $interactions = CrmInteraction::where('tenant_id', $tenantId)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays($days))
            ->where('is_completed', false)
            ->with(['user', 'interactable'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn($i) => $this->formatInteraction($i));

        return response()->json([
            'success' => true,
            'interactions' => $interactions,
        ]);
    }

    /**
     * Get overdue tasks
     */
    public function overdue(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $interactions = CrmInteraction::where('tenant_id', $tenantId)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->where('is_completed', false)
            ->with(['user', 'interactable'])
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn($i) => $this->formatInteraction($i));

        return response()->json([
            'success' => true,
            'interactions' => $interactions,
        ]);
    }

    /**
     * Quick add note
     */
    public function quickNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,customer,prospect',
            'entity_id' => 'required|integer',
            'content' => 'required|string|max:2000',
        ]);

        $entityClass = match ($validated['entity_type']) {
            'lead' => Lead::class,
            'customer' => Customer::class,
            'prospect' => Prospect::class,
        };

        $entity = $entityClass::findOrFail($validated['entity_id']);

        $interaction = CrmInteraction::create([
            'tenant_id' => $request->user()->tenant_id,
            'interactable_type' => $entityClass,
            'interactable_id' => $entity->id,
            'user_id' => $request->user()->id,
            'type' => 'note',
            'content' => $validated['content'],
        ]);

        $entity->update(['last_activity_at' => now()]);

        return response()->json([
            'success' => true,
            'interaction' => $this->formatInteraction($interaction->load('user')),
        ]);
    }

    /**
     * Log a quick call
     */
    public function logCall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,customer,prospect',
            'entity_id' => 'required|integer',
            'direction' => 'required|in:inbound,outbound',
            'outcome' => 'required|string|max:255',
            'duration_seconds' => 'nullable|integer|min:0',
            'content' => 'nullable|string|max:2000',
        ]);

        $entityClass = match ($validated['entity_type']) {
            'lead' => Lead::class,
            'customer' => Customer::class,
            'prospect' => Prospect::class,
        };

        $entity = $entityClass::findOrFail($validated['entity_id']);

        $interaction = CrmInteraction::create([
            'tenant_id' => $request->user()->tenant_id,
            'interactable_type' => $entityClass,
            'interactable_id' => $entity->id,
            'user_id' => $request->user()->id,
            'type' => 'call',
            'direction' => $validated['direction'],
            'outcome' => $validated['outcome'],
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'content' => $validated['content'] ?? null,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $entity->update([
            'last_activity_at' => now(),
            'last_contacted_at' => now(),
        ]);

        // If lead, update first_contacted_at if not set
        if ($validated['entity_type'] === 'lead' && !$entity->first_contacted_at) {
            $entity->update(['first_contacted_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'interaction' => $this->formatInteraction($interaction->load('user')),
        ]);
    }

    /**
     * Schedule a task/meeting
     */
    public function schedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|in:lead,customer,prospect',
            'entity_id' => 'required|integer',
            'type' => 'required|in:meeting,task,call,visit',
            'subject' => 'required|string|max:255',
            'content' => 'nullable|string|max:2000',
            'scheduled_at' => 'required|date|after:now',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'reminder_at' => 'nullable|date|before:scheduled_at',
        ]);

        $entityClass = match ($validated['entity_type']) {
            'lead' => Lead::class,
            'customer' => Customer::class,
            'prospect' => Prospect::class,
        };

        $entity = $entityClass::findOrFail($validated['entity_id']);

        $interaction = CrmInteraction::create([
            'tenant_id' => $request->user()->tenant_id,
            'interactable_type' => $entityClass,
            'interactable_id' => $entity->id,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'subject' => $validated['subject'],
            'content' => $validated['content'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
            'priority' => $validated['priority'] ?? 'normal',
            'reminder_at' => $validated['reminder_at'] ?? null,
            'is_completed' => false,
        ]);

        return response()->json([
            'success' => true,
            'interaction' => $this->formatInteraction($interaction->load('user')),
            'message' => ucfirst($validated['type']) . ' programmé(e)',
        ]);
    }

    // ==========================================
    // PRIVATE METHODS
    // ==========================================

    private function getTimelineData($entity, Request $request): array
    {
        $typeFilter = $request->input('type');
        $userFilter = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Build query
        $query = $entity->interactions()
            ->with(['user', 'attachments'])
            ->when($typeFilter, fn($q) => $q->where('type', $typeFilter))
            ->when($userFilter, fn($q) => $q->where('user_id', $userFilter))
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo));

        // Get all interactions
        $interactions = $query->orderBy('created_at', 'desc')->get();

        // Also include system events (status changes, etc.) from activity logs
        $activityLogs = $this->getActivityLogs($entity);

        // Merge and sort by date
        $allItems = collect();

        foreach ($interactions as $interaction) {
            $allItems->push([
                'id' => 'interaction_' . $interaction->id,
                'type' => 'interaction',
                'data' => $this->formatInteraction($interaction),
                'date' => $interaction->created_at,
            ]);
        }

        foreach ($activityLogs as $log) {
            $allItems->push([
                'id' => 'activity_' . $log['id'],
                'type' => 'activity',
                'data' => $log,
                'date' => $log['created_at'],
            ]);
        }

        $sortedItems = $allItems->sortByDesc('date')->values();

        // Calculate stats
        $stats = $this->calculateStats($interactions);

        return [
            'items' => $sortedItems,
            'stats' => $stats,
            'filters' => [
                'types' => CrmInteraction::getTypeOptions(),
            ],
        ];
    }

    private function getActivityLogs($entity): array
    {
        // Get activity logs for this entity from the activity_logs table
        $logs = \DB::table('activity_logs')
            ->where('subject_type', get_class($entity))
            ->where('subject_id', $entity->id)
            ->whereIn('event', ['created', 'updated', 'status_change'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return $logs->map(function ($log) {
            $properties = json_decode($log->properties, true) ?? [];

            return [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'properties' => $properties,
                'user_id' => $log->causer_id,
                'created_at' => $log->created_at,
                'icon' => $this->getActivityIcon($log->event),
                'color' => $this->getActivityColor($log->event),
            ];
        })->toArray();
    }

    private function formatInteraction(CrmInteraction $interaction): array
    {
        return [
            'id' => $interaction->id,
            'type' => $interaction->type,
            'formatted_type' => $interaction->formatted_type,
            'icon' => $interaction->icon,
            'color' => $interaction->color,
            'subject' => $interaction->subject,
            'content' => $interaction->content,
            'outcome' => $interaction->outcome,
            'direction' => $interaction->direction,
            'duration_seconds' => $interaction->duration_seconds,
            'formatted_duration' => $interaction->formatted_duration,
            'scheduled_at' => $interaction->scheduled_at?->format('Y-m-d H:i'),
            'completed_at' => $interaction->completed_at?->format('Y-m-d H:i'),
            'is_completed' => $interaction->is_completed,
            'is_overdue' => $interaction->scheduled_at && !$interaction->is_completed && $interaction->scheduled_at->isPast(),
            'priority' => $interaction->priority,
            'sentiment' => $interaction->sentiment,
            'reminder_at' => $interaction->reminder_at?->format('Y-m-d H:i'),
            'created_at' => $interaction->created_at->format('Y-m-d H:i'),
            'created_at_human' => $interaction->created_at->diffForHumans(),
            'user' => $interaction->user ? [
                'id' => $interaction->user->id,
                'name' => $interaction->user->name,
                'avatar' => $interaction->user->avatar_url ?? null,
            ] : null,
            'attachments' => $interaction->attachments->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'size' => $a->formatted_size,
                'url' => $a->url,
                'icon' => $a->icon,
                'is_image' => $a->isImage(),
            ]),
            'entity' => $interaction->interactable ? [
                'type' => class_basename($interaction->interactable_type),
                'id' => $interaction->interactable_id,
                'name' => $interaction->interactable->full_name ?? $interaction->interactable->name ?? 'N/A',
            ] : null,
        ];
    }

    private function formatEntityData($entity, string $type): array
    {
        $data = [
            'id' => $entity->id,
            'type' => $type,
            'name' => $entity->full_name ?? "{$entity->first_name} {$entity->last_name}",
            'email' => $entity->email,
            'phone' => $entity->phone ?? $entity->mobile ?? null,
            'company' => $entity->company ?? $entity->company_name ?? null,
            'status' => $entity->status,
            'created_at' => $entity->created_at->format('d/m/Y'),
            'last_contacted_at' => $entity->last_contacted_at?->format('d/m/Y H:i'),
            'last_activity_at' => $entity->last_activity_at?->format('d/m/Y H:i'),
        ];

        if ($type === 'lead') {
            $data['score'] = $entity->score;
            $data['priority'] = $entity->priority;
            $data['source'] = $entity->source;
            $data['assigned_to'] = $entity->assignedTo?->name;
        }

        if ($type === 'customer') {
            $data['total_revenue'] = $entity->total_revenue;
            $data['outstanding_balance'] = $entity->outstanding_balance;
            $data['total_contracts'] = $entity->total_contracts;
        }

        return $data;
    }

    private function calculateStats($interactions): array
    {
        $total = $interactions->count();
        $byType = $interactions->groupBy('type')->map->count();
        $lastWeek = $interactions->filter(fn($i) => $i->created_at >= now()->subWeek())->count();
        $completedTasks = $interactions->where('is_completed', true)->count();
        $pendingTasks = $interactions->whereIn('type', ['task', 'meeting', 'call'])
            ->where('is_completed', false)
            ->whereNotNull('scheduled_at')
            ->count();

        return [
            'total' => $total,
            'by_type' => $byType,
            'last_week' => $lastWeek,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'calls' => $byType['call'] ?? 0,
            'emails' => ($byType['email'] ?? 0) + ($byType['email_received'] ?? 0),
            'meetings' => $byType['meeting'] ?? 0,
            'notes' => $byType['note'] ?? 0,
        ];
    }

    private function getActivityIcon(string $event): string
    {
        return match ($event) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'status_change' => 'exchange-alt',
            'deleted' => 'trash',
            default => 'info-circle',
        };
    }

    private function getActivityColor(string $event): string
    {
        return match ($event) {
            'created' => 'green',
            'updated' => 'blue',
            'status_change' => 'purple',
            'deleted' => 'red',
            default => 'gray',
        };
    }
}
