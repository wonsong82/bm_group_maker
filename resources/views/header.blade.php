<header>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <a class="navbar-brand" href="#">{{ $title }}</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav mr-auto">
                {{--<li class="nav-item active">
                    <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Link</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">Disabled</a>
                </li>--}}
            </ul>
            <div class="mt-2 mt-md-0">
                <a href="{{ url('/') }}" class="btn btn-outline-info my-2 my-sm-0">Home</a>

                <button data-toggle="modal" data-target="#saved-list" class="btn btn-outline-primary my-2 my-sm-0">Saved List</button>

                @if($save)
                <button data-toggle="modal" data-target="#save" class="btn btn-outline-success my-2 my-sm-0">Save</button>
                @endif
            </div>

        </div>
    </nav>
</header>



<form class="form-inline mt-2 mt-md-0">
    <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
    <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
</form>



<!-- Modal -->
<div class="modal fade" id="saved-list" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Saved List</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <tbody>
                    @foreach($list as $result)
                        <tr>
                            <td>{{$result->name}}</td>
                            <td align="right">{{$result->updated_at->diffForHumans()}}</td>
                            <td align="right">
                                <button class="load-btn btn btn-sm btn-outline-primary" data-id="{{$result->id}}">Load</button>
                                @if(!$result->temp)
                                    <button class="delete-btn btn btn-sm btn-outline-danger" data-id="{{$result->id}}">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>




<div class="modal fade" id="save" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="name-input" placeholder="Name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary save-btn">Save</button>
            </div>
        </div>
    </div>
</div>


<script>
    $(function(){
        $('.load-btn').click(function(){
            var id = $(this).attr('data-id');
            window.location.href = '{{ url('load') }}' + '/' + id;
            return false;
        });

        $('.save-btn').click(function(){
            var input = $('#name-input').val();
            if(!input){
                alert('Name must be provided');
                return false;
            }

            $.ajax({
                url: '{{ url('save') }}',
                data: {
                    name: input
                },
                type: 'get',
                success: function(id){
                    window.location.href = '{{ url('load') }}' + '/' + id;
                }
            });


        });


        $('.delete-btn').click(function(){
            var id = $(this).attr('data-id');
            $.ajax({
                url: '{{ url('delete') }}' + '/' + id,
                type: 'get',
                success: function(){
                    alert('Item deleted');
                    window.location.href = window.location.href;
                }
            })
        });

    });
</script>
