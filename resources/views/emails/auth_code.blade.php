@component('mail::message')
# Your Secret Code

Hello, {{ $user->first_name .' '. $user->last_name }}

Your secret code is: {{ $code }}

Please speak your code to event manager and get in, good luck!

Thanks,<br/>
StrikeTec!
@endcomponent