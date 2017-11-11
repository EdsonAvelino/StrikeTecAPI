@component('mail::message')
# Write Us : Query

Hello Admin, 

You have got the email.

From: {{ $email }}

Subject: <b>{{ $sub }}</b>

Message: {{ $msg }}

Thanks,<br/>
StrikeTec!
@endcomponent