@extends ('backend.layouts.app')
@section ('title', 'Upload Video')
@section('page-header')
    <h1>
       Add Video
    </h1>
@endsection

@section('content')
      {!! Form::open(array('url' => 'admin/save','files'=>true)) !!}
                
        <div class="box box-success">
            <div class="box-body">
                @if(Session('error_message'))
                    <p class="alert alert-danger">{{ Session('error_message') }}</p>
                @endif    
                @if(session('Status'))
                            <div class="alert alert-success alert-dismissable">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                {{session('Status')}}
                            </div>
                @endif
                <div class="form-group">
                   <div class="box-body">  
                        {{ Form::label('name', 'Select Category', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                            <select class="form-control" name="cat">
                                @if(isset($category))
                                     @foreach($category as $cat)
                                      <option value="{{$cat->id}}">{{$cat->name}}</option>
                                    @endforeach
                                @endif   
                            </select>
                        </div>
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Tag', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                            @if(isset($videoTagList))
                                @foreach($videoTagList as $val)
                                  {{ Form::checkbox('video_tag[]', $val->id)}} {{$val->name}}
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Video Title', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                        {{ Form::text('title',null, ['class' => 'form-control', 'maxlength' => '191', 'required' => 'required', 'autofocus' => 'autofocus','placeholder' => 'Enter Video Title']) }}         
                        </div><!--col-lg-10-->
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Author Name', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                        {{ Form::text('author_name',null, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter Author Name']) }}         
                        </div>
                    </div>
                    <div class="box-body">
                        {{ Form::label('price', 'price', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                        {{ Form::text('price', null, ['class' => 'form-control', 'maxlength' => '191', 'autofocus' => 'autofocus','placeholder' => 'Enter video price']) }}         
                        </div><!--col-lg-10-->
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Upload Video (MP4/50MB)', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                            {!! Form::file('video_file', array('required','class' => 'form-control')) !!}
                        </div>
                    </div>
                     <div class="box-body">
                        {{ Form::label('name', 'Video Thumbnail (JPEG/JPG/PNG)', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                            {!! Form::file('video_thumbnail', array('class' => 'form-control')) !!}
                        </div>
                    </div>
                 </div><!--form control-->
            </div><!-- /.box-body -->
        </div><!--box-->

        <div class="box box-info">
            <div class="box-body">
                <div class="pull-left">
                    <a class="btn btn-danger" href="{{{ route('admin.videos.list') }}}">{{trans('buttons.general.cancel')}}</a>
             
                </div><!--pull-left-->

                <div class="pull-right">
                      <button type="submit" class="btn btn-success">Submit</button>
               
                </div><!--pull-right-->

                <div class="clearfix"></div>
            </div><!-- /.box-body -->
        </div><!--box-->
    {!! Form::close() !!}
@endsection

@section('after-scripts')
    {{ Html::script('js/backend/access/users/script.js') }}
@endsection
