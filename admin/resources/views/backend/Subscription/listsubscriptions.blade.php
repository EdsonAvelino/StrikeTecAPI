@extends ('backend.layouts.app')

@section ('title', 'Manage Subscription')

@section('after-styles')
{{ Html::style("https://cdn.datatables.net/v/bs/dt-1.10.15/datatables.min.css") }}
{{ Html::style("https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css") }}
@endsection

@section('page-header')
<h1>
   Manage Subscriptions
</h1>
@endsection
@section('content')
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"></h3>

        <div class="box-tools pull-right">
            
        </div><!--box-tools pull-right-->
        <div class="pull-left">
                <a href="{{route('admin.subscriptionplan.add')}}" class="btn btn-primary" >Add Subscription</a>
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
            <table id="videos-table" class="table table-condensed table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Tutorials</th>
                        <th>Tournaments</th>
                        <th>Battles</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                @if(count($subscriptionLists)>0)
                @foreach($subscriptionLists as $subscriptionList)
                <tr>
                    <td>{{$subscriptionList->SKU}}</td>
                    <td>{{$subscriptionList->name}}</td>
                    <td>{{$subscriptionList->tutorials}}</td>
                    <td>{{$subscriptionList->tournaments}}</td>
                    <td>{{$subscriptionList->battles}}</td>
                    <td>{{$subscriptionList->price}}</td>
                    @if($subscriptionList->status === 0)
                    <td> Active</td>
                    @else<td>Inactive</td>
                    @endif
                    <td>
                        <a href="{{ route('admin.subscriptionplan.add', ['id' => $subscriptionList->id]) }}">
                            <i class="fa fa-pencil" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit"></i>
                        </a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                        <a href="#" class='btn btn-xs btn-danger' onclick="deletesubsConfirm({{$subscriptionList->id}})">
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


@endsection

@section('after-scripts')
{{ Html::script("https://cdn.datatables.net/v/bs/dt-1.10.15/datatables.min.js") }}
{{ Html::script("js/backend/plugin/datatables/dataTables-extend.js") }}
<script>
    $(function() {
        $('#videos-table').DataTable({
            'aoColumnDefs': [{
                    'bSortable': false,
                    'aTargets': [-1] /* 1st one, start by the right */
            }],
            "pageLength": 10
        });
    });
   function deletesubsConfirm(id) {
        swal({
            title: "Are you sure?",
            text: "You want to delete subscription.!",
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
                window.location.href = "{{route('delete.subs')}}/" + id;
            } else {
                swal("Cancelled", "Your subscription details is safe :)", "error");
            }
        });
   }
</script>

@endsection
