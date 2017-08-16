@component('mail::message')
# Password Reset Code

Hello, {{ $user->first_name .' '. $user->last_name }}

Your password reset code is {{ $code }}

Thanks,<br/>
StrikeTec!
@endcomponent