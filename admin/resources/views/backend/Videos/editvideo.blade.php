@extends ('backend.layouts.app')
@section ('title', 'Upload Video')
@section('page-header')
    <h1>
       Edit Video
    </h1>
@endsection

@section('content')
              {{ Form::open(['url' => 'admin/update/'.$video->id, 'files'=>true ,'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post']) }}
            @if(Session('error_message'))
                <p class="alert alert-danger">{{ Session('error_message') }}</p>
            @endif    
        <div class="box box-success">
            <div class="box-header with-border">
                  
            </div><!-- /.box-header -->
            <div class="box-body">
               
                <div class="form-group">
                   <div class="box-body">  
                        {{ Form::label('name', 'Select Category', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                           
                            <select class="form-control" name="cat">
                                
                                @if(isset($category))
                                     @foreach($category as $cat)
                                      <option value="{{$cat->id}}"
                                               @if($cat->name == $selected_cat)
                                                   {{'selected'}}
                                                @endif
                                      >{{$cat->name}}</option>
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
                                <input type="checkbox" name="video_tag[]" value="{{$val->id}}" @foreach($tagged_video as $list) @if($list->tag_id == $val->id) checked @endif @endforeach >{{$val->name}}
                                @endforeach
                            @endif
                        </div>
                    </div>
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Video Title', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                        {{ Form::text('title',$video->title, ['class' => 'form-control', 'maxlength' => '191', 'autofocus' => 'autofocus','placeholder' => 'Enter Video Title']) }}         
                        </div><!--col-lg-10-->
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Author Name', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                        {{ Form::text('author_name', $video->author_name, ['class' => 'form-control','autofocus' => 'autofocus','placeholder' => 'Enter Author Name']) }}         
                        </div>
                    </div>
                    <div class="box-body">
                        {{ Form::label('price', 'price', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-10">
                        {{ Form::text('price', $video->price, ['class' => 'form-control', 'maxlength' => '191', 'autofocus' => 'autofocus','placeholder' => 'Enter video price']) }}         
                        </div><!--col-lg-10-->
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Upload Video (MP4/50MB)', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-5">
                            {!! Form::file('video_file', array('class' => 'form-control')) !!}
                        </div>
                        <div class="col-lg-5"><i class="fa fa-eye" aria-hidden="true"></i>&nbsp;&nbsp;
                            <a href="#" class="video_modal" data-toggle="modal"  data-original-title="Delete" data-target="#video">Preview</a><br>
                        </div>
                    </div>
                    <div class="box-body">
                        {{ Form::label('name', 'Video Thumbnail (JPEG/JPG/PNG)', ['class' => 'col-lg-2 control-label']) }}
                        <div class="col-lg-5">
                            {!! Form::file('video_thumbnail', array('class' => 'form-control')) !!}
                        </div>
                        <div class="col-lg-5">  <i class="fa fa-eye" aria-hidden="true"></i>&nbsp;&nbsp;
                            <a href="#" class="video_modal" data-toggle="modal" data-target="#thumbnail">Preview</a><br>
                        </div>
                    </div>
                </div><!--form control-->
            </div><!-- /.box-body -->
        </div><!--box-->

        <div class="box box-info">
            <div class="box-body">
                <div class="pull-left">
                    <a class="btn btn-danger btn-xs" href="{{ route('admin.videos.list') }}">{{trans('buttons.general.cancel')}}</a>
             
                </div><!--pull-left-->

                <div class="pull-right">
                      <button type="submit" class="btn btn-success">Update</button>
               
                </div><!--pull-right-->

                <div class="clearfix"></div>
            </div><!-- /.box-body -->
        </div><!--box-->
        <div id="video" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Video</h4>
                    </div>
                    <div class="modal-body" id="video_tag">
                        <?php if(!empty($video->file)){ ?>
                          <video style="width: 100%; height: 100%;" controls><source type="video/mp4" src="{{ env('API_URL_STORAGE') }}/videos/{{$video->file}}"></video>    
                        <?php } else { ?>
                            <img src="{{env('API_URL_STORAGE').'/videos/no_preview_video.jpg'}}" style="width: 100%; height: 100%;">
                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="thumbnail" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Thumbnail</h4>
                    </div>
                    <div class="modal-body" id="video_tag">
                        <?php if(!empty($video->thumbnail)){ ?>
                            <img src="{{env('API_URL_STORAGE').'/videos/thumbnails/'.$video->thumbnail}}" style="width: 100%; height: 100%;">
                        <?php } else { ?>
                            <img src="{{env('API_URL_STORAGE').'/videos/thumbnails/no_preview_image.jpg'}}" style="width: 100%; height: 50%;">
                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    {!! Form::close() !!}
@endsection

@section('after-scripts')
    {{ Html::script('js/backend/access/users/script.js') }}
@endsection
