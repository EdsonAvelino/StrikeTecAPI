@component('mail::message')
# Password For New User

Hello, {{ $name }}

Your password is {{ $code }}

Thanks,<br/>
StrikeTec!
@endcomponent