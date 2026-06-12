<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyContactRequest;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::with(['user', 'repliedBy'])
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
                    ->orWhere('admin_reply', 'like', $term);
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->string('date'));
        }

        if ($request->string('status') === 'pending') {
            $query->whereNull('admin_reply');
        } elseif ($request->string('status') === 'replied') {
            $query->whereNotNull('admin_reply');
        }

        $messages = $query->paginate($request->integer('per_page', 50));

        return response()->json(['data' => $messages]);
    }

    public function reply(ReplyContactRequest $request, int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $reply = $request->validated()['reply'];

        $message->update([
            'admin_reply' => $reply,
            'replied_by' => auth()->id(),
            'replied_at' => now(),
        ]);

        $message->load(['user', 'repliedBy']);

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

        return response()->json([
            'data' => [
                'id' => $message->id,
                'email' => $message->email,
                'sujet' => $message->sujet,
                'message' => $message->message,
                'admin_reply' => $message->admin_reply,
                'replied_at' => $message->replied_at,
                'replied_by' => $message->repliedBy,
                'mail_sent' => $mailSent,
            ],
            'message' => $mailSent
                ? 'Réponse enregistrée et envoyée par email.'
                : 'Réponse enregistrée, mais l\'envoi email a échoué.',
        ]);
    }
}
