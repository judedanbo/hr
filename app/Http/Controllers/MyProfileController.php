<?php

namespace App\Http\Controllers;

use App\Services\StaffProfileProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyProfileController extends Controller
{
    public function __construct(protected StaffProfileProvider $provider) {}

    public function show(Request $request): Response
    {
        $personId = $request->user()->person_id;
        abort_unless($personId, 403, 'Your account is not linked to a staff record.');

        $payload = $this->provider->forPerson($personId);
        abort_if($payload === null, 404, 'Staff record not found.');

        return Inertia::render('MyProfile/Index', $payload);
    }
}
