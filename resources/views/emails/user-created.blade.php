@component('mail::message')
<h2 @class([ '' ])>Dear {{$user->name}} </h2>
<br>
<p>Your account has been created successfully.</p>
<p>Here are your login details:</p>
<br>
<p>Email: {{ $user->email }}</p>
<p>Password: {{ $password }}</p>

@component('mail::button', ['url' => url('/login')])
Login
@endcomponent

Thank you,<br>
{{ config('app.name') }}
@endcomponent