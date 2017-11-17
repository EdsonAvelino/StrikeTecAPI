@extends ('backend.layouts.app')
@section ('title', 'Subscription plan')
@section('page-header')
<h1>
    Subscription Plan
</h1>
@endsection

@section('content')
    @include('backend.Subscription._form', ['form_action'=>'register.subs', 'form_title'=>'Add Subscription Plan'])
@endsection

@section('after-scripts')
{{ Html::script('js/backend/access/users/script.js') }}
@endsection
