<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Prospect;
use App\Models\Site;
use App\Models\User;
use App\Models\VideoAgentStatus;
use App\Models\VideoCall;
use App\Models\VideoCallInvitation;
use App\Models\VideoCallMessage;
use App\Models\VideoSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VideoCallController extends Controller
{
    /**
     * Video calls dashboard
     */
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        // Get video settings
        $settings = VideoSettings::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'video_enabled' => true,
                'working_hours' => VideoSettings::getDefaultWorkingHours(),
            ]
        );

        // Today's scheduled calls
        $todaysCalls = VideoCall::where('tenant_id', $tenantId)
            ->today()
            ->with(['agent', 'site', 'customer', 'prospect'])
            ->orderBy('scheduled_at')
            ->get();

        // Waiting room
        $waitingCalls = VideoCall::where('tenant_id', $tenantId)
            ->waiting()
            ->with(['site'])
            ->orderBy('updated_at')
            ->get();

        // Active calls
        $activeCalls = VideoCall::where('tenant_id', $tenantId)
            ->inProgress()
            ->with(['agent', 'site'])
            ->get();

        // Available agents
        $availableAgents = VideoAgentStatus::where('tenant_id', $tenantId)
            ->available()
            ->with('user')
            ->get();

        // Online agents
        $onlineAgents = VideoAgentStatus::where('tenant_id', $tenantId)
            ->online()
            ->with(['user', 'currentCall'])
            ->get();

        // Stats
        $stats = [
            'today_total' => $todaysCalls->count(),
            'today_completed' => VideoCall::where('tenant_id', $tenantId)
                ->today()
                ->completed()
                ->count(),
            'waiting' => $waitingCalls->count(),
            'in_progress' => $activeCalls->count(),
            'agents_online' => $onlineAgents->count(),
            'agents_available' => $availableAgents->count(),
        ];

        // Recent calls
        $recentCalls = VideoCall::where('tenant_id', $tenantId)
            ->completed()
            ->with(['agent', 'site'])
            ->orderBy('ended_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Tenant/VideoCall/Index', [
            'settings' => $settings,
            'todaysCalls' => $todaysCalls,
            'waitingCalls' => $waitingCalls,
            'activeCalls' => $activeCalls,
            'onlineAgents' => $onlineAgents,
            'availableAgents' => $availableAgents,
            'recentCalls' => $recentCalls,
            'stats' => $stats,
            'isWithinWorkingHours' => $settings->isWithinWorkingHours(),
        ]);
    }

    /**
     * Schedule a new video call
     */
    public function schedule(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);
        $agents = User::where('tenant_id', $tenantId)
            ->whereHas('videoAgentStatus')
            ->get(['id', 'name', 'email']);
        $customers = Customer::where('tenant_id', $tenantId)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);
        $prospects = Prospect::where('tenant_id', $tenantId)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        return Inertia::render('Tenant/VideoCall/Schedule', [
            'sites' => $sites,
            'agents' => $agents,
            'customers' => $customers,
            'prospects' => $prospects,
            'types' => VideoCall::TYPES,
        ]);
    }

    /**
     * Store a scheduled call
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(VideoCall::TYPES)),
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'agent_id' => ['nullable', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
            'customer_id' => ['nullable', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'prospect_id' => ['nullable', 'exists:prospects,id', new \App\Rules\SameTenantResource(\App\Models\Prospect::class, $tenantId)],
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string|max:50',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string',
            'send_invitation' => 'boolean',
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['room_id'] = Str::uuid()->toString();

        $call = VideoCall::create($validated);

        // Create and send invitation if requested
        if ($request->input('send_invitation')) {
            $email = $validated['guest_email'];
            if (!$email && $validated['customer_id']) {
                $email = Customer::find($validated['customer_id'])->email;
            } elseif (!$email && $validated['prospect_id']) {
                $email = Prospect::find($validated['prospect_id'])->email;
            }

            if ($email) {
                $invitation = $call->createInvitation($email, $validated['guest_phone'] ?? null);
                $invitation->markAsSent();
                // TODO: Send email notification
            }
        }

        return redirect()->route('tenant.video-calls.index')
            ->with('success', 'Appel vidéo planifié avec succès.');
    }

    /**
     * Show call details
     */
    public function show(VideoCall $videoCall): Response
    {
        $this->authorize('view', $videoCall);

        $videoCall->load(['agent', 'site', 'customer', 'prospect', 'messages', 'invitations']);

        return Inertia::render('Tenant/VideoCall/Show', [
            'call' => $videoCall,
        ]);
    }

    /**
     * Agent video room
     */
    public function agentRoom(VideoCall $videoCall): Response
    {
        $this->authorize('update', $videoCall);

        $settings = VideoSettings::where('tenant_id', Auth::user()->tenant_id)->first();

        return Inertia::render('Tenant/VideoCall/AgentRoom', [
            'call' => $videoCall->load(['site', 'customer', 'prospect']),
            'settings' => $settings,
            'iceServers' => $this->getIceServers($settings),
        ]);
    }

    /**
     * Start a call from waiting room
     */
    public function startCall(VideoCall $videoCall)
    {
        $this->authorize('update', $videoCall);

        $user = Auth::user();

        // Assign agent if not already assigned
        if (!$videoCall->agent_id) {
            $videoCall->agent_id = $user->id;
        }

        $videoCall->start();

        // Update agent status
        $agentStatus = VideoAgentStatus::firstOrCreate(
            ['user_id' => $user->id],
            ['tenant_id' => $user->tenant_id, 'status' => 'online']
        );
        $agentStatus->startCall($videoCall);

        return redirect()->route('tenant.video-calls.agent-room', $videoCall);
    }

    /**
     * End a call
     */
    public function endCall(Request $request, VideoCall $videoCall)
    {
        $this->authorize('update', $videoCall);

        $validated = $request->validate([
            'summary' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $videoCall->end();
        $videoCall->update($validated);

        // Update agent status
        $agentStatus = VideoAgentStatus::where('user_id', Auth::id())->first();
        if ($agentStatus) {
            $agentStatus->endCall();
        }

        return redirect()->route('tenant.video-calls.show', $videoCall)
            ->with('success', 'Appel terminé.');
    }

    /**
     * Cancel a call
     */
    public function cancel(VideoCall $videoCall)
    {
        $this->authorize('update', $videoCall);

        $videoCall->cancel();

        return back()->with('success', 'Appel annulé.');
    }

    /**
     * Send chat message
     */
    public function sendMessage(Request $request, VideoCall $videoCall)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'type' => 'in:text,file,image',
            'file_url' => 'nullable|url',
        ]);

        $user = Auth::user();

        $message = $videoCall->messages()->create([
            'user_id' => $user->id,
            'sender_type' => 'agent',
            'sender_name' => $user->name,
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'text',
            'file_url' => $validated['file_url'] ?? null,
        ]);

        return response()->json($message);
    }

    /**
     * Get call messages
     */
    public function getMessages(VideoCall $videoCall)
    {
        return response()->json(
            $videoCall->messages()
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * Update agent status
     */
    public function updateAgentStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:online,busy,away,offline',
        ]);

        $user = Auth::user();

        $agentStatus = VideoAgentStatus::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id' => $user->tenant_id,
                'status' => $validated['status'],
                'status_changed_at' => now(),
                'last_activity_at' => now(),
            ]
        );

        return response()->json($agentStatus);
    }

    /**
     * Ping to keep agent status alive
     */
    public function pingAgent()
    {
        $agentStatus = VideoAgentStatus::where('user_id', Auth::id())->first();

        if ($agentStatus) {
            $agentStatus->ping();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Video settings page
     */
    public function settings(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $settings = VideoSettings::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'video_enabled' => true,
                'working_hours' => VideoSettings::getDefaultWorkingHours(),
            ]
        );

        $agents = User::where('tenant_id', $tenantId)
            ->with('videoAgentStatus')
            ->get();

        return Inertia::render('Tenant/VideoCall/Settings', [
            'settings' => $settings,
            'agents' => $agents,
        ]);
    }

    /**
     * Update video settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'video_enabled' => 'boolean',
            'recording_enabled' => 'boolean',
            'chat_enabled' => 'boolean',
            'screen_sharing_enabled' => 'boolean',
            'waiting_room_enabled' => 'boolean',
            'max_call_duration_minutes' => 'integer|min:5|max:180',
            'max_concurrent_calls' => 'integer|min:1|max:20',
            'welcome_message' => 'nullable|string|max:500',
            'waiting_room_message' => 'nullable|string|max:500',
            'notification_emails' => 'nullable|array',
            'working_hours' => 'nullable|array',
        ]);

        $settings = VideoSettings::updateOrCreate(
            ['tenant_id' => Auth::user()->tenant_id],
            $validated
        );

        return back()->with('success', 'Paramètres mis à jour.');
    }

    /**
     * Call history
     */
    public function history(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $query = VideoCall::where('tenant_id', $tenantId)
            ->with(['agent', 'site', 'customer', 'prospect'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $calls = $query->paginate(20);

        $agents = User::where('tenant_id', $tenantId)
            ->whereHas('videoCalls')
            ->get(['id', 'name']);

        return Inertia::render('Tenant/VideoCall/History', [
            'calls' => $calls,
            'agents' => $agents,
            'filters' => $request->only(['status', 'type', 'agent_id', 'date_from', 'date_to']),
            'statuses' => VideoCall::STATUSES,
            'types' => VideoCall::TYPES,
        ]);
    }

    /**
     * Get ICE servers configuration
     */
    private function getIceServers(?VideoSettings $settings): array
    {
        // Default public STUN servers
        $defaultServers = [
            ['urls' => 'stun:stun.l.google.com:19302'],
            ['urls' => 'stun:stun1.l.google.com:19302'],
        ];

        if ($settings && $settings->ice_servers) {
            // Parse custom ICE servers if configured
            return json_decode($settings->ice_servers, true) ?: $defaultServers;
        }

        return $defaultServers;
    }

    /**
     * Create instant call (walk-in)
     */
    public function createInstant(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string|max:50',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'type' => 'in:' . implode(',', array_keys(VideoCall::TYPES)),
        ]);

        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['room_id'] = Str::uuid()->toString();
        $validated['status'] = 'waiting';
        $validated['type'] = $validated['type'] ?? 'tour';

        $call = VideoCall::create($validated);

        return response()->json([
            'call' => $call,
            'join_url' => $call->join_url,
        ]);
    }
}
