<?php

namespace App\Actions\Messages;

use App\Models\AssistantMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ReplyToAssistant
{
    /** @param Collection<int, AssistantMessage> $history */
    public function reply(string $question, Collection $history, User $user): string
    {
        $apiKey = config('services.openai.key');

        if (is_string($apiKey) && $apiKey !== '') {
            try {
                $response = Http::withToken($apiKey)
                    ->connectTimeout(3)
                    ->timeout(15)
                    ->post('https://api.openai.com/v1/responses', [
                        'model' => config('services.openai.model'),
                        'store' => false,
                        'max_output_tokens' => 300,
                        'instructions' => $this->instructions($user),
                        'input' => $history->map(fn (AssistantMessage $message): array => [
                            'role' => $message->role,
                            'content' => $message->body,
                        ])->all(),
                    ]);

                if ($response->successful()) {
                    $output = collect($response->json('output', []))
                        ->flatMap(fn (array $item): array => $item['content'] ?? [])
                        ->filter(fn (array $item): bool => ($item['type'] ?? null) === 'output_text')
                        ->pluck('text')
                        ->implode("\n");

                    if (trim($output) !== '') {
                        return trim($output);
                    }
                }
            } catch (Throwable) {
                // Keep local help available when the provider is unavailable.
            }
        }

        return $this->localReply($question, $user);
    }

    private function instructions(User $user): string
    {
        $behavior = 'You are the FixTrack assistant. Answer the latest user question directly, briefly, and in the same language as the user. Use the app facts below for FixTrack questions. A greeting needs a greeting, and a navigation question needs navigation steps. Do not repeat a general feature list as an answer to a specific question. If the question is unclear, ask one specific clarifying question. If these facts do not establish an answer, say you are unsure and suggest Support & Help for an account issue. You cannot inspect this user\'s live bookings, prices, availability, payment status, or account details. Never invent those details, policies, or promises.';

        if ($user->isTechnician()) {
            return $behavior.' FixTrack facts for technicians: Dashboard shows an overview and availability. Go online before reviewing and accepting available requests in Job Requests. Accepted requests become assigned jobs in My Jobs; update the job there as en route, in progress, or completed. Use Schedule for appointments, Dispatch & Routes for route information, Walk-In for shop queue tickets, Earnings for completed-job and cash payment records, and Verification & Profile for account details. Open a job in My Jobs to prepare a quotation; the customer reviews it before approval. Messages opens this assistant first, with customer conversations below it after booking acceptance. Support & Help handles account-specific problems.';
        }

        return $behavior.' Customer app facts: On desktop use the sidebar; on mobile the bottom tabs are Home, Activity, Messages, and Account. Start a request from Book a Service or the Book a service button on mobile Home. Choose quick or manual booking; manual booking offers home service or walk-in. Provide the service, contact details, and address, plus a preferred time for a scheduled home service. Track requests and active-booking cancellation in My Bookings or mobile Activity. A booking may be cancelled only while the app offers Cancel booking; a reason is required. For walk-in, choose a service category and shop, join the available queue, and track the ticket in Walk-in Queue. Review technician quotations in Quotations. Completed-service payment records are in Payments, and payment is cash only. Messages opens this assistant first; a technician conversation appears below it only after that technician accepts a booking. Support & Help handles account-specific problems.';
    }

    private function localReply(string $question, User $user): string
    {
        $normalized = ' '.(string) Str::of($question)
            ->lower()
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->squish().' ';
        $mentions = static function (array $terms) use ($normalized): bool {
            foreach ($terms as $term) {
                if (str_contains($normalized, ' '.$term.' ')) {
                    return true;
                }
            }

            return false;
        };

        if (in_array(trim($normalized), ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'], true)) {
            return 'Hi! What would you like help with in FixTrack?';
        }

        $asksAboutNavigation = $mentions(['navigate', 'navigation', 'use this system', 'use this app', 'use fixtrack', 'how does fixtrack work', 'get around', 'show me around', 'where do i start', 'how do i start']);

        if ($user->isTechnician()) {
            return match (true) {
                $mentions(['cancel', 'cancelled', 'cancellation']) => 'Open the active job in My Jobs and choose Cancel job if that action is available. If the job cannot be cancelled there, open Support & Help.',
                $mentions(['support', 'account issue', 'report a problem']) => 'Open Support & Help to send the team the details of your account or job issue. I cannot check your private account status here.',
                $mentions(['walk in', 'walkin', 'queue', 'ticket']) => 'Open Walk-In to review your shop queue, call the next customer, and update each ticket as service progresses.',
                $mentions(['quotation', 'quote', 'estimate', 'labor', 'materials']) => 'Open the assigned job in My Jobs and prepare a quotation with assessment notes, labor, and materials. The customer can review it in Quotations.',
                $mentions(['earnings', 'earning', 'payment', 'paid', 'cash']) => 'Open Earnings to review completed jobs and recorded cash payments. For a specific payment issue, open Support & Help.',
                $mentions(['accept', 'request', 'requests']) => 'Open Job Requests while you are online. Review an available matching request and accept it. The accepted job appears in My Jobs and opens a customer conversation in Messages.',
                $mentions(['online', 'offline', 'availability', 'available']) => 'Use the availability control on Dashboard or Job Requests to go online before accepting new work. Active jobs must be completed before going offline.',
                $mentions(['message', 'messages', 'chat', 'customer conversation']) => 'Open Messages. This assistant is first; a customer conversation appears below after you accept their booking. Select that conversation to reply.',
                $mentions(['schedule', 'appointment']) => 'Open Schedule to review upcoming appointments and service timing.',
                $mentions(['job', 'jobs', 'status', 'progress']) => 'Open My Jobs to view assigned work and update an active job as en route, in progress, or completed.',
                $mentions(['profile', 'verification', 'account', 'password']) => 'Open Verification & Profile for your technician details. For an account or password problem, open Support & Help.',
                $asksAboutNavigation => 'Start at Dashboard for your overview and availability. Open Job Requests to accept work, My Jobs to update active jobs, Schedule for appointments, Walk-In for queue tickets, Earnings for payment records, and Messages for customer chats and this assistant.',
                $mentions(['what can you do', 'help with']) => 'I can guide you through Job Requests, My Jobs, Schedule, Walk-In, Earnings, and Messages. Which task are you working on?',
                default => 'I am not sure which FixTrack task you mean. Are you asking about a job request, an active job, a quotation, or your account?',
            };
        }

        return match (true) {
            $mentions(['cancel', 'cancelled', 'cancellation']) => 'For an active home-service booking, open My Bookings (or Activity on mobile), select it, and choose Cancel booking. Enter a reason to confirm. For a walk-in ticket, use Cancel Walk-In in Walk-in Queue.',
            $mentions(['support', 'account issue', 'report a problem']) => 'Open Support & Help to send the team details about a booking, payment, or account issue. I cannot check your private account records here.',
            $mentions(['walk in', 'walkin', 'queue', 'ticket', 'shop']) => 'For a walk-in, open Book a Service, choose manual booking and Walk-In, select a service category and shop, then join the available queue. Track your ticket in Walk-in Queue.',
            $mentions(['quotation', 'quote', 'estimate', 'labor', 'materials']) => 'When a technician sends a quotation, open Quotations to review the assessment, labor, materials, and total before approving or declining it.',
            $mentions(['payment', 'pay', 'paid', 'cash', 'receipt']) => 'FixTrack records cash payments. Open Payments to review the payment record after a service is completed. For a specific payment problem, open Support & Help.',
            $mentions(['message', 'messages', 'chat', 'contact technician', 'talk to technician', 'accept', 'accepted']) => 'After a technician accepts your booking, their conversation appears below this assistant in Messages with an opening message. Select that chat to reply.',
            $mentions(['track', 'status', 'progress', 'where is my booking', 'where are my bookings', 'find my booking', 'my bookings']) => 'Open My Bookings (or Activity on mobile), then select your request to see its current status, technician, and service details.',
            $asksAboutNavigation => 'Use the sidebar on desktop or Home, Activity, Messages, and Account on mobile. Start in Book a Service (or tap Book a service on mobile Home), track requests in My Bookings or Activity, review Quotations and Payments, and open Messages for this assistant or an accepted-booking technician chat.',
            $mentions(['book', 'booking', 'schedule', 'service', 'technician']) => 'Open Book a Service, choose quick or manual booking, select the service, and enter your contact details and address. A scheduled home service also needs a preferred date and time. Track the request in My Bookings.',
            $mentions(['profile', 'settings', 'account', 'password']) => 'Open Account on mobile or Settings in the desktop sidebar to manage your profile and security. For an account problem, open Support & Help.',
            $mentions(['what can you do', 'help with']) => 'I can guide you through Book a Service, My Bookings, Walk-in Queue, Quotations, Payments, and Messages. Which task are you trying to complete?',
            default => 'I am not sure which FixTrack task you mean. Are you asking about booking, tracking a request, messaging a technician, or your account?',
        };
    }
}
