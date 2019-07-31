<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <script
            src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
            crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

</head>
<body>
    @include('header', ['save' => false, 'title' => '그룹만들기'])

    <main style="padding-top: 80px" class="container-fluid">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-body">

                        <form
                                action="{{ route('makeGroup') }}"
                                method="POST"
                                enctype="multipart/form-data"
                        >
                            {{ csrf_field() }}
                            {{ method_field('POST') }}


                            <div class="form-group">
                                <label for="">데이타 엑셀</label>
                                <input type="file" class="form-control" name="file">
                            </div>

                            <div class="form-group">
                                <label for="">그룹 설정</label>
                                <textarea name="config" id="config" class="form-control" cols="30" rows="10">@include('default_conf')</textarea>
                            </div>

                            <hr>

                            <div class="float-right">
                                <input type="submit" class="btn btn-primary" value="그룹만들기" id="submit">
                            </div>

                        </form>

                    </div>
                </div>



            </div>
        </div>
    </>
    <script>
        $(function(){
            $('form').submit(function(){
                $('#submit').attr('value', '만드는중...').attr('disabled', 'disabled');
            });
        });
    </script>

</body>
</html>
