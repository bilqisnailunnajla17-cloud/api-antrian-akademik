<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TicketQueueService
{
    /**
     * Issue a globally increasing ticket number for each service.
     * All writers MUST lock the corresponding services row first.
     * The sequence intentionally does not reset daily to avoid collisions
     * if ticket_number is unique without a date component.
     */
    public function take(int $serviceId, string $studentNim): Ticket
    {
        return DB::transaction(function () use ($serviceId, $studentNim): Ticket {
            $service = Service::query()->whereKey($serviceId)->lockForUpdate()->first();

            if (!$service || !$service->is_active) {
                throw ValidationException::withMessages([
                    'service_id' => ['Layanan tidak ditemukan atau sedang tidak aktif.'],
                ]);
            }

            $prefix = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($service->code));
            if ($prefix === '') {
                throw ValidationException::withMessages([
                    'service_id' => ['Kode layanan tidak valid.'],
                ]);
            }

            // This read happens AFTER we lock the service row. Never use
            // MAX(id) alone: we need to derive the sequence from the last
            // ticket number, not from the table's primary key.
            $latestNumber = Ticket::query()
                ->where('service_id', $service->id)
                ->orderByDesc('id')
                ->value('ticket_number');

            $next = 1;
            if ($latestNumber !== null) {
                $matched = preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $latestNumber, $m);
                if (!$matched) {
                    throw new \RuntimeException('Format nomor tiket terakhir tidak sesuai: ' . $latestNumber);
                }
                $next = (int) $m[1] + 1;
            }

            $ticket = Ticket::query()->create([
                'service_id' => $service->id,
                'counter_id' => null,
                'student_nim' => $studentNim,
                'ticket_number' => $prefix . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT),
                'status' => 'waiting',
            ]);

            TicketLog::query()->create([
                'ticket_id' => $ticket->id,
                'officer_name' => null,
                'action' => 'created',
            ]);

            return $ticket->load(['service', 'counter']);
        }, 5);
    }

    /**
     * Lock the selected counter, then atomically claim one waiting ticket.
     * Row-level ticket lock prevents two counters from calling the same ticket.
     */
    public function callNext(int $counterId, string $officerName): Ticket
    {
        return DB::transaction(function () use ($counterId, $officerName): Ticket {
            $counter = Counter::query()->whereKey($counterId)->lockForUpdate()->first();

            if (!$counter || $counter->status !== 'active') {
                throw ValidationException::withMessages([
                    'counter_id' => ['Loket tidak ditemukan atau sedang tidak aktif.'],
                ]);
            }

            $ticket = Ticket::query()
                ->where('status', 'waiting')
                ->whereDate('created_at', today())
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                throw new ConflictHttpException('Tidak ada antrean yang menunggu.');
            }

            $ticket->update([
                'counter_id' => $counter->id,
                'status' => 'called',
            ]);

            TicketLog::query()->create([
                'ticket_id' => $ticket->id,
                'officer_name' => $officerName,
                'action' => 'called',
            ]);

            return $ticket->load(['service', 'counter']);
        }, 5);
    }
}
