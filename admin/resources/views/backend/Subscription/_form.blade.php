{!! Form::model($subscriptionDetail, ['route' => $form_action, 'method' => 'post']) !!}

<div class="box box-success">
    <div class="box-header with-border">
        {{$form_title}}
    </div>
    <div class="box-body">
        @if(session('Status'))
        <!--<div class="alert alert-success alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            {{session('Status')}}
        </div>-->
        @endif
        
        <!-- Start falsh message-->
        @if(Session::has('flash_error_message'))
            <div class="alert alert-danger">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                {{ Session::get('flash_error_message') }}
            </div>
        @endif

        @if(Session::has('flash_success_message'))
            <div class="alert alert-success">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                {{ Session::get('flash_success_message') }}
            </div>
        @endif
        <!--End flash meassege div-->
        {{ Form::hidden('id', null)}}
        <div class="form-group">
            <div class="box-body">  
                {{ Form::label('SKU', 'SKU *', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('SKU', null, ['class' => 'form-control', 'maxlength' => '191',  'autofocus' => 'autofocus','placeholder' => 'Enter SKU']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('name', 'Name', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('name', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter name']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('tutorials', 'Tutorials *', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('tutorials', null, ['class' => 'form-control', 'maxlength' => '191',  'autofocus' => 'autofocus','placeholder' => 'Enter tutorials']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('tournaments', 'Tournaments *', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('tournaments', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter tournament name']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('battles', 'Battles', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('battles', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter battles name']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('tournament_details', 'Tournament Details', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('tournament_details', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter tournament details']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('battle_details', 'Battles Details', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('battle_details', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter battel details']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('tutorial_details', 'Tutorials Details', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('tutorial_details', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter tutorials details']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('duration', 'Duration', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::select('duration', ['month' => 'Month', 'until_exhausted' => 'Until Exhausted'], null, ['class' => 'form-control','autofocus' => 'autofocus']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('price', 'Price', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::text('price', null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter price']) }}         
                </div>
            </div>
            <div class="box-body">
                {{ Form::label('status', 'Status', ['class' => 'col-lg-2 control-label']) }}
                <div class="col-lg-10">
                    {{ Form::select('status', ['Active','Inactive'], null, ['class' => 'form-control','autofocus' => 'autofocus']) }}         
                </div>
            </div>
        </div><!--form control-->
    </div><!-- /.box-body -->
</div><!--box-->
<div class="box box-info">
    <div class="box-body">
        <div class="pull-left">
            <a class="btn btn-danger" href="{{{ route('admin.subscriptionplan.list.subs') }}}">{{trans('buttons.general.cancel')}}</a>
        </div><!--pull-left-->
        <div class="pull-right">
            <button type="submit" class="btn btn-success">Submit</button>
        </div><!--pull-right-->
        <div class="clearfix"></div>
    </div><!-- /.box-body -->
</div><!--box-->
{!! Form::close() !!}