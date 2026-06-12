<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyContactRequest;
use App\Http\Requests\UpdateContactStatusRequest;
use App\Http\Resources\ContactMessageResource;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::with(['user', 'replier', 'replies'])
            ->withCount('replies')
            ->orderByDesc('created_at');

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->string('email').'%');
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('email', 'like', $term)
                    ->orWhere('sujet', 'like', $term)
                    ->orWhere('message', 'like', $term)
                    ->orWhere('admin_reply', 'like', $term)
                    ->orWhereHas('replies', fn ($builder) => $builder->where('body', 'like', $term));
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->string('date'));
        }

        if ($request->filled('status') && in_array($request->string('status'), ['pending', 'replied', 'closed'], true)) {
            $query->where('status', $request->string('status'));
        }

        $messages = $query->paginate($request->integer('per_page', 50));
        $messages->getCollection()->transform(
            fn (ContactMessage $message) => (new ContactMessageResource($message))->resolve()
        );

        return response()->json(['data' => $messages]);
    }

    public function updateStatus(UpdateContactStatusRequest $request, int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $status = $request->validated()['status'];

        $message->update(['status' => $status]);
        $message->load(['user', 'replier', 'replies']);
        $message->loadCount('replies');

        return response()->json([
            'data' => new ContactMessageResource($message),
            'message' => 'Statut mis à jour.',
        ]);
    }

    public function reply(ReplyContactRequest $request, int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $reply = $request->validated()['reply'];

        $mailSent = false;
        try {
            Mail::to($message->email)->send(new ContactReplyMail($message, $reply));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error('Contact reply email failed', [
                'contact_message_id' => $message->id,
                'email' => $message->email,
                'error' => $e->getMessage(),
            ]);
        }

        ContactMessageReply::create([
            'contact_message_id' => $message->id,
            'admin_id' => auth()->id(),
            'body' => $reply,
            'mail_sent' => $mailSent,
            'created_at' => now(),
        ]);

        $message->update([
            'admin_reply' => $reply,
            'replied_by' => auth()->id(),
            'replied_at' => now(),
            'status' => ContactMessage::STATUS_REPLIED,
        ]);

        $message->load(['user', 'replier', 'replies']);
        $message->loadCount('replies');

        return response()->json([
            'data' => array_merge(
                (new ContactMessageResource($message))->resolve(),
                ['mail_sent' => $mailSent],
            ),
            'message' => $mailSent
                ? 'Réponse enregistrée et envoyée par email.'
                : 'Réponse enregistrée, mais l\'envoi email a échoué.',
        ]);
    }
}
