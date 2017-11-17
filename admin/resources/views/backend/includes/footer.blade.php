<!-- Main Footer -->
<footer class="main-footer">
    
    <!-- Start datatable js library here paste file path -->
    
    <!-- End datatable js library -->
    
    <!-- Start Sweat alert java script include -->
    {{ Html::script("https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js") }}
    <!-- End Sweat alert java script include -->
    
    <!-- To the right -->
    <div class="pull-right hidden-xs">
        <a href='{{ url('/') }}' target="_blank">StrikeTec-TKCS</a>
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; {{ date('Y') }} <a href="#">StrikeTec-TKCS</a>.</strong> {{ trans('strings.backend.general.all_rights_reserved') }}
</footer>