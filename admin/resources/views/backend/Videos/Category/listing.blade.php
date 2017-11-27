@extends ('backend.layouts.app')

@section ('title', 'Manage Category')

@section('after-styles')
    {{ Html::style("https://cdn.datatables.net/v/bs/dt-1.10.15/datatables.min.css") }}
    {{ Html::style("https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css") }}
@endsection

@section('page-header')
    <h1>
        {{trans('labels.backend.video_category.listing.page_title')}}
    </h1>
@endsection

@section('content')
    <div class="box box-success">
        <div class="box-header with-border">
            <!--<h3 class="box-title">{{trans('labels.backend.video_category.listing.box_title')}}</h3>-->
            <div class="pull-left">
                <a href="{{route('admin.videos.category.create')}}" class="btn btn-primary">Add Category</a>
            </div><!--box-tools pull-right-->
        </div><!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                @if(session('Status'))
                    <div class="alert alert-success alert-dismissable">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        {{session('Status')}}
                    </div>
                @endif
                <table id="videos-table" class="table table-condensed table-hover">
                    <thead>
                        <tr>
                            <th>{{ trans('labels.backend.video_category.listing.col_head') }}</th>
                            <th>{{ trans('labels.backend.video_category.listing.col_action') }}</th>
                        </tr>
                    </thead>
                    @if(isset($category))
                        @foreach($category as $cat)
                        <tr>
                            <td>
                                {{$cat->name}}
                            </td>
                            <td>
                                <a href="{{ url('admin/category/edit/'.$cat->id) }}">
                                    <i class="fa fa-pencil" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"></i>
                                </a>&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="#" class='btn btn-xs btn-danger' onclick="delcatConfirm({{$cat->id}})">
                                    <i class="fa fa-trash-o" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete"></i>
                                </a> 
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="2">No data to display</td>
                        </tr>
                        @endif   
                </table>
            </div><!--table-responsive-->
        </div><!-- /.box-body -->
    </div><!--box-->


@endsection

@section('after-scripts')
    {{ Html::script("https://cdn.datatables.net/v/bs/dt-1.10.15/datatables.min.js") }}
    {{ Html::script("js/backend/plugin/datatables/dataTables-extend.js") }}

    <script>
        $(function () {
            $('#videos-table').DataTable({
                "pageLength": 10,
                'aoColumnDefs': [{
                    'bSortable': false,
                    'aTargets': [-1] /* 1st one, start by the right */
            }],
            });
        });
        function delcatConfirm(id) {
            swal({
                title: "Are you sure?",
                text: "You want to delete category. All the videos related to this category will be deleted.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                closeOnCancel: false
            },
            function(isConfirm){
                if (isConfirm) {   
                   window.location.href="{{url('admin/category/delete')}}/"+id;
                } else {
                    swal("Cancelled", "Your category details is safe :)", "error");
                }
            });
        }
    </script>
@endsection
