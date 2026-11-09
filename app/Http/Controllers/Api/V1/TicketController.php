<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CallNextTicketRequest;
use App\Http\Requests\TakeTicketRequest;
use App\Models\Ticket;
use App\Services\TicketQueueService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = Ticket::query()
            ->with(['service', 'counter'])
            ->whereDate('created_at', today())
            ->whereIn('status', ['waiting', 'called'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $tickets]);
    }

    public function store(TakeTicketRequest $request, TicketQueueService $queue): JsonResponse
    {
        $ticket = $queue->take(
            (int) $request->validated('service_id'),
            (string) $request->validated('student_nim'),
        );

        return response()->json(['data' => $ticket], 201);
    }

    public function callNext(CallNextTicketRequest $request, TicketQueueService $queue): JsonResponse
    {
        try {
            $ticket = $queue->callNext(
                (int) $request->validated('counter_id'),
                (string) $request->validated('officer_name'),
            );
        } catch (ConflictHttpException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'QUEUE_EMPTY',
            ], 409);
        }

        return response()->json(['data' => $ticket]);
    }
}
