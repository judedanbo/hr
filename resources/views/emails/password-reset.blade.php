@component('mail::message')
<h2>Dear {{$user->name}} </h2>
<br>
<p>A new password created for you.</p>
<p>your can now login with the following details :</p>
<br>
<p>Email: {{ $user->email }}</p>
<p>Password: {{ $password }}</p>

@component('mail::button', ['url' => url('/login')])
Login
@endcomponent

Thank you,<br>
{{ config('app.name') }}
@endcomponent