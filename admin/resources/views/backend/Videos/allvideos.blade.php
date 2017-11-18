@extends ('backend.layouts.app')

@section ('title', 'Manage Videos')

@section('after-styles')
    {{ Html::style("https://cdn.datatables.net/v/bs/dt-1.10.15/datatables.min.css") }}
    {{ Html::style("https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css") }}
@endsection

@section('page-header')
    <h1>
       Manage Videos
    </h1>
@endsection

@section('content')
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"></h3>
        <div class=" pull-left">
            <a href="{{route('admin.videos.upload')}}" class="btn btn-primary">Add Video</a>
        </div><!--box-tools pull-right-->
        <div class="col-sm-8 pull-right">
            <div id="filters"><span class="col-sm-6 control-label pull-right" style="margin-right: -4.5%;"></span></div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
             @if(session('Status'))
                <div class="alert alert-success alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    {{session('Status')}}
                </div>
            @endif
            <table id="example" class="display dataTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>File / Duration</th>
                        <th>Category</th>
                        <th>Thumbnail</th>
                        <th>Author</th>
                        <th>Action</th>
                    </tr>
                </thead>
                @if(isset($videos))
                    @foreach($videos as $video)
                        <tr>
                            <td>{{$video->title}}</td>
                            <td>
                               <a href="#" class="video_modal" data-toggle="modal" data-target="#video" id="{{$video->file}}" onclick="data_video('{{$video->file}}')">{{str_replace('http://striketec.dev/uploads/videos/', '', $video->file)}}</a><br>{{$video->duration}}
                            </td>
                            <td>
                               {{$video->name}}
                            </td>
                            <td>
                                <img src='{{$video->thumbnail}}' style="width: 50px; height: 50px;">
                            </td>
                            <td>
                                {{$video->author_name}}
                            </td>
                            <td>
                                <a href="{{ url('admin/video/'.$video->id) }}">
                                    <i class="fa fa-pencil" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"></i>
                                </a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="#" class='btn btn-xs btn-danger' onclick="delvideoConfirm({{$video->id}})">
                                    <i class="fa fa-trash-o" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"></i>
                                </a> 
                             </td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="2">No data to display</td></tr>
                @endif  
            </table>
        </div><!--table-responsive-->
    </div><!-- /.box-body -->
</div><!--box-->
    <!-- Modal -->
<div id="video" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Video</h4>
            </div>
            <div class="modal-body" id="video_tag">
               
                <!--<video width="540" height="310" controls>
                    <source type="video/mp4" src="http://striketec.dev/uploads/videos/video_1509980909.mp4" >
                </video>-->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('after-scripts')
    {{ Html::script("https://cdn.datatables.net/v/bs/dt-1.10.15/datatables.min.js") }}
    {{ Html::script("js/backend/plugin/datatables/dataTables-extend.js") }}
    
    <script>
        /* Function for call delete API */
        function delvideoConfirm(id) { 
            swal({
                title: "Are you sure?",
                text: "You want to delete video.!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel please!",
                closeOnConfirm: false,
                closeOnCancel: false
            },
            function(isConfirm){
                if (isConfirm) {   
                    window.location.href = "{{url('admin/delete')}}/"+id;
                } else {
                    swal("Cancelled", "Your video details is safe :)", "error");
                }
            });
        }
         
        /*function delvideoConfirm(id) {
                var flag=confirm("Do you really want to delete this video?");
                if (flag==true)
                    window.location.href="{{url('admin/video/delete')}}/"+id;
        }*/
       
        /* Function for add video in modal */
        function data_video(id) {
            var video = '';
            var  video = '<video width="540" height="310" controls><source type="video/mp4" src="'+id+'"></video>';
                $('#video_tag').html(video);
        }
        
        /* video modal closed closed button */
        $("#video .close").click();
        
        /* Function for datatables and create custom filter for category */
        $(document).ready(function () {
            $('#example').DataTable({
                'aoColumnDefs': [{
                    'bSortable': false,
                    'aTargets': [-1] /* 1st one, start by the right */
                }],
                initComplete: function () {
                    this.api().columns(2).every(function () {
                        var column = this;
                        var select = $('<select class="form-control"><option value="">Filter by category</option><option value="">All</option></select>')
                            .appendTo($("#filters").find("span"))
                            .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val());                                     

                            column.search(val ? '^' + val + '$' : '', true, false)
                                .draw();
                        });
                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>')
                        });
                    });
                }
            });
        });
    </script>

@endsection
