<?php

namespace App\Http\Controllers;

use App\Enums\ContactTypeEnum;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Traits\LogsAuthorization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    use LogsAuthorization;

    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): Response
    {
        $this->logSuccess('viewed all contacts');

        $contacts = Contact::query()
            ->with('person')
            ->when($request->contact_type, fn ($q, $type) => $q->where('contact_type', $type))
            ->when($request->search, function ($q, $search) {
                $q->where('contact', 'like', "%{$search}%")
                    ->orWhereHas('person', function ($query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Contact $contact) => [
                'id' => $contact->id,
                'contact_type' => $contact->contact_type?->value,
                'contact_type_label' => $contact->contact_type?->label(),
                'contact' => $contact->contact,
                'valid_end' => $contact->valid_end?->format('d M Y'),
                'person_id' => $contact->person_id,
                'person_name' => $contact->person?->full_name,
                'is_active' => $contact->valid_end === null || $contact->valid_end->isFuture(),
                'created_at' => $contact->created_at?->format('d M Y'),
            ]);

        // Get contact types for filters
        $contactTypes = collect(ContactTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return Inertia::render('Contact/Index', [
            'contacts' => $contacts,
            'filters' => $request->only(['search', 'contact_type']),
            'contactTypes' => $contactTypes,
        ]);
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create(): Response
    {
        $contactTypes = collect(ContactTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return Inertia::render('Contact/Create', [
            'contactTypes' => $contactTypes,
        ]);
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(StoreContactRequest $request)
    {
        $contact = Contact::create($request->validated());

        $this->logSuccess('created a contact', $contact);

        return redirect()->back()->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): Response
    {
        $this->logSuccess('viewed contact details', $contact);

        $contact->load('person');

        return Inertia::render('Contact/Show', [
            'contact' => [
                'id' => $contact->id,
                'contact_type' => $contact->contact_type?->value,
                'contact_type_label' => $contact->contact_type?->label(),
                'contact' => $contact->contact,
                'valid_end' => $contact->valid_end?->format('d M Y'),
                'person_id' => $contact->person_id,
                'person_name' => $contact->person?->full_name,
                'is_active' => $contact->valid_end === null || $contact->valid_end->isFuture(),
                'created_at' => $contact->created_at?->format('d M Y H:i'),
                'updated_at' => $contact->updated_at?->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact)
    {
        $contactTypes = collect(ContactTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return [
            'contact' => [
                'id' => $contact->id,
                'contact_type' => $contact->contact_type?->value,
                'contact' => $contact->contact,
                'valid_end' => $contact->valid_end?->format('Y-m-d'),
                'person_id' => $contact->person_id,
            ],
            'contactTypes' => $contactTypes,
        ];
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        $this->logSuccess('updated a contact', $contact);

        return redirect()->back()->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        if ($contact->contact_type === ContactTypeEnum::PHONE) {
            $remaining = Contact::query()
                ->where('person_id', $contact->person_id)
                ->where('contact_type', ContactTypeEnum::PHONE)
                ->whereNull('valid_end')
                ->where('id', '!=', $contact->id)
                ->count();

            if ($remaining === 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'contact' => 'You must keep at least one active phone number.',
                ]);
            }
        }

        if ($contact->isProtectedOrgEmail()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'contact' => 'This Audit Service email address cannot be deleted.',
            ]);
        }

        $this->logSuccess('deleted a contact', $contact);

        $contact->delete();

        return redirect()->route('contact.index')->with('success', 'Contact deleted successfully.');
    }
}
