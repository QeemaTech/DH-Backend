<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::query()
            ->latest()
            ->paginate(20);

        return view('admin.contact-messages.index', compact('messages'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        if ($contactMessage->viewed_at === null) {
            $contactMessage->forceFill(['viewed_at' => now()])->save();
        }

        return view('admin.contact-messages.show', [
            'message' => $contactMessage->fresh(),
        ]);
    }
}
