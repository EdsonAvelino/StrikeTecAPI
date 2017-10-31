@component('mail::message')
# Write us : query

Hello Admin, 

You have got the email.

From    [  {{ $user }} ]

Subject   [  <b>{{ $subject }}</b>]

Message     [ {{ $message }} ].

Thanks,<br/>
StrikeTec!
@endcomponent