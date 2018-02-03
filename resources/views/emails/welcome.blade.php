@component('mail::message')
# Welcome

Hello! {{ $user->first_name .' '. $user->last_name }}

Welcome to StrikeTec

You can access StrikeTec using following credentials

Your Email: {{ $user->email }}<br/>
Your password is <b>{{ $code }}</b>

Thanks,<br/>
StrikeTec!
@endcomponent