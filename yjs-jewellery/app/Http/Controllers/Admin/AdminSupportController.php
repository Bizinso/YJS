<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\CannedResponse;
use App\Models\EmailTemplate;
use App\Models\CommunicationLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Support Controller
 *
 * Handles support tickets, canned responses, email templates,
 * and customer communications.
 */
class AdminSupportController extends Controller
{
    // ============ TICKETS ============

    /**
     * List all support tickets.
     */
    public function tickets(Request $request): JsonResponse
    {
        $query = SupportTicket::with([
            'user:id,name,email,phone',
            'order:id,custom_order_code,order_total',
            'assignedTo:id,name',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->unassigned) {
            $query->unassigned();
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Get ticket statistics.
     */
    public function ticketStats(): JsonResponse
    {
        $stats = [
            'open' => SupportTicket::open()->count(),
            'unassigned' => SupportTicket::unassigned()->open()->count(),
            'urgent' => SupportTicket::priority('urgent')->open()->count(),
            'high' => SupportTicket::priority('high')->open()->count(),
            'pending_customer' => SupportTicket::status(SupportTicket::STATUS_PENDING_CUSTOMER)->count(),
            'resolved_today' => SupportTicket::whereDate('resolved_at', today())->count(),
            'avg_response_time' => $this->getAverageResponseTime(),
            'by_category' => SupportTicket::open()
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get single ticket details.
     */
    public function showTicket(int $id): JsonResponse
    {
        $ticket = SupportTicket::with([
            'user:id,name,email,phone',
            'order:id,custom_order_code,order_total,order_status',
            'assignedTo:id,name,email',
            'messages.sender:id,name,email',
            'statusHistory.changedByUser:id,name',
        ])->find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Assign ticket to an agent.
     */
    public function assignTicket(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = SupportTicket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        $ticket->assignTo($request->agent_id, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Ticket assigned successfully',
            'data' => $ticket->fresh()->load('assignedTo:id,name,email'),
        ]);
    }

    /**
     * Update ticket status.
     */
    public function updateTicketStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,assigned,in_progress,pending_customer,resolved,closed',
            'notes' => 'nullable|string|max:1000',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = SupportTicket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        if ($request->resolution_notes) {
            $ticket->resolution_notes = $request->resolution_notes;
            $ticket->save();
        }

        $ticket->updateStatus($request->status, $request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated',
            'data' => $ticket->fresh(),
        ]);
    }

    /**
     * Reply to a ticket.
     */
    public function replyToTicket(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
            'attachments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = SupportTicket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        $message = $ticket->addMessage(
            auth()->id(),
            'admin',
            $request->message,
            $request->attachments,
            $request->is_internal ?? false
        );

        // Update status if replying to open/pending ticket
        if (in_array($ticket->status, [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_ASSIGNED])) {
            $ticket->updateStatus(SupportTicket::STATUS_IN_PROGRESS);
        }

        // Log communication if not internal note
        if (!$request->is_internal) {
            CommunicationLog::log(
                $ticket->user_id,
                CommunicationLog::CHANNEL_TICKET,
                CommunicationLog::TYPE_TICKET_RESPONSE,
                $request->message,
                "Re: {$ticket->subject}",
                $ticket->order_id,
                $ticket->user->email ?? null,
                ['ticket_id' => $ticket->id, 'message_id' => $message->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply sent',
            'data' => $message->load('sender:id,name,email'),
        ]);
    }

    // ============ CANNED RESPONSES ============

    /**
     * List canned responses.
     */
    public function cannedResponses(Request $request): JsonResponse
    {
        $query = CannedResponse::with('createdByUser:id,name');

        if ($request->category) {
            $query->category($request->category);
        }
        if ($request->active_only !== false) {
            $query->active();
        }
        if ($request->search) {
            $query->search($request->search);
        }

        $responses = $query->orderBy('usage_count', 'desc')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $responses,
        ]);
    }

    /**
     * Create canned response.
     */
    public function createCannedResponse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'shortcode' => 'required|string|max:50|unique:canned_responses',
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = CannedResponse::create([
            'title' => $request->title,
            'shortcode' => $request->shortcode,
            'content' => $request->content,
            'category' => $request->category,
            'variables' => $request->variables,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Canned response created',
            'data' => $response,
        ], 201);
    }

    /**
     * Update canned response.
     */
    public function updateCannedResponse(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'shortcode' => 'nullable|string|max:50|unique:canned_responses,shortcode,' . $id,
            'content' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = CannedResponse::find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Canned response not found',
            ], 404);
        }

        $response->update($request->only(['title', 'shortcode', 'content', 'category', 'variables', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Canned response updated',
            'data' => $response->fresh(),
        ]);
    }

    /**
     * Delete canned response.
     */
    public function deleteCannedResponse(int $id): JsonResponse
    {
        $response = CannedResponse::find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Canned response not found',
            ], 404);
        }

        $response->delete();

        return response()->json([
            'success' => true,
            'message' => 'Canned response deleted',
        ]);
    }

    // ============ EMAIL TEMPLATES ============

    /**
     * List email templates.
     */
    public function emailTemplates(Request $request): JsonResponse
    {
        $query = EmailTemplate::with('updatedByUser:id,name');

        if ($request->category) {
            $query->category($request->category);
        }

        $templates = $query->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Get single email template.
     */
    public function showEmailTemplate(int $id): JsonResponse
    {
        $template = EmailTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Update email template.
     */
    public function updateEmailTemplate(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = EmailTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);
        }

        $template->update(array_merge(
            $request->only(['name', 'subject', 'body_html', 'body_text', 'is_active']),
            ['updated_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Template updated',
            'data' => $template->fresh(),
        ]);
    }

    // ============ COMMUNICATIONS ============

    /**
     * Get customer communication history.
     */
    public function customerCommunications(Request $request, int $customerId): JsonResponse
    {
        $customer = User::find($customerId);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $query = CommunicationLog::forUser($customerId)
            ->with('order:id,custom_order_code', 'sentByUser:id,name');

        if ($request->channel) {
            $query->channel($request->channel);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $communications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $communications,
        ]);
    }

    /**
     * Send notification to customer.
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'channel' => 'required|in:email,sms,whatsapp,push',
            'type' => 'required|string|max:50',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($request->user_id);
        $recipient = match ($request->channel) {
            'email' => $user->email,
            'sms', 'whatsapp' => $user->phone,
            default => null,
        };

        $log = CommunicationLog::log(
            $request->user_id,
            $request->channel,
            $request->type,
            $request->content,
            $request->subject,
            $request->order_id,
            $recipient
        );

        // Here you would integrate with actual notification services
        // For now, we mark it as sent
        $log->markSent();

        return response()->json([
            'success' => true,
            'message' => 'Notification queued',
            'data' => $log,
        ]);
    }

    /**
     * Helper: Get average first response time.
     */
    private function getAverageResponseTime(): ?float
    {
        $result = SupportTicket::whereNotNull('first_response_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->first();

        return $result->avg_minutes ? round($result->avg_minutes / 60, 1) : null; // hours
    }
}
