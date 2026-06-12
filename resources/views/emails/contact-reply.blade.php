Bonjour,

{{ $replyBody }}

---
Votre message initial ({{ $contactMessage->created_at?->format('d/m/Y H:i') ?? '—' }}) :
Sujet : {{ $contactMessage->sujet }}

{{ $contactMessage->message }}

--
Équipe CYNA
contact@cyna-it.fr
