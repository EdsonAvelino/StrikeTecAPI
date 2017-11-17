@extends ('backend.layouts.app')

@section ('title', 'Create Video Category')
@section('page-header')
    <h1>
       
       @if(isset($cat_id))
           Update Video Categories
        @else
            {{trans('labels.backend.video_category.create.page_title')}}
        @endif
    </h1>
@endsection

@section('content')
    @if(isset($cat_id))
        {{ Form::open(['url' => 'admin/videos/category/update/'.$cat_id, 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post']) }}
     @else
        {{ Form::open(['url' => 'admin/videos/category/save/', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post']) }}
    @endif
   
        <div class="box box-success">
            <div class="box-header with-border">
                @if(isset($cat_id))
                    <h3 class="box-title"> Update </h3>
                @else
                    <h3 class="box-title">{{trans('labels.backend.video_category.create.box_title')}}</h3>
                @endif
            </div><!-- /.box-header -->
            <div class="box-body">
                <div class="form-group">
                    {{ Form::label('Name', trans('labels.backend.video_category.create.field_label'), ['class' => 'col-lg-2 control-label']) }}
                    <div class="col-lg-10">
                        @if(isset($cat_name))
                                {{ Form::text('name', $cat_name, ['required','class' => 'form-control', 'maxlength' => '191', 'required' => 'required', 'autofocus' => 'autofocus']) }}
                        @else
                                 {{ Form::text('name', null, ['required','class' => 'form-control', 'maxlength' => '191', 'required' => 'required', 'autofocus' => 'autofocus','placeholder' => trans('labels.backend.video_category.create.field_placeholder')]) }}
                        @endif
                    </div><!--col-lg-10-->
                </div><!--form control-->
            </div><!-- /.box-body -->
        </div><!--box-->

        <div class="box box-info">
            <div class="box-body">
                <div class="pull-left">
                    <a class="btn btn-danger btn-xs" href="{{{ route('admin.videos.category.list') }}}">{{trans('buttons.general.cancel')}}</a>
             
                </div><!--pull-left-->

                <div class="pull-right">
                    @if(isset($cat_id))
                        {{ Form::submit('Update', ['class' => 'btn btn-success btn-xs']) }}
                    @else
                        {{ Form::submit(trans('buttons.general.crud.create'), ['class' => 'btn btn-success btn-xs']) }}
                    @endif
                </div><!--pull-right-->

                <div class="clearfix"></div>
            </div><!-- /.box-body -->
        </div><!--box-->

    {{ Form::close() }}
@endsection

@section('after-scripts')
    {{ Html::script('js/backend/access/users/script.js') }}
@endsection
