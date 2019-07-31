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
@include('header', ['save' => true, 'title' => '그룹 ' . number_format($trial) . '번 결과' ])


<br>
<main class="container-fluid">
    <div class="row">

        @php
        $i = 0;
        @endphp
        @foreach($groups as $group)
            @php
            $i++;
            @endphp
            <div class="col-md-6">
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-body">

                        <h5 class="card-title">그룹 {{ $i }}</h5>

                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>이름</th>
                                <th>성별</th>
                                <th>생일</th>
                                <th>전화번호</th>
                                <th>레벨</th>
                                <th>정보</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group as $user)
                                <tr>
                                    <td>{{ $user['name'] }}</td>
                                    <td>{{ $user['gender'] }}</td>
                                    <td>{{ $user['dob'] }}</td>
                                    <td>{{ $user['phone'] }}</td>
                                    <td>{{ $user['level'] }}</td>
                                    <td>{{ $user['info'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <table class="table table-bordered">
                            <tr>
                                <td>나이차: {{ $group->ageGap }}</td>
                                <td>평군나이: {{ round($group->average('year')) }}</td>
                                <td>레벨: A {{ $group->levelA }}, B {{ $group->levelB }}, C {{ $group->levelC }}, D {{ $group->levelD }}, E {{ $group->levelE }}</td>
                                <td>남녀: 남 {{ $group->male }}, 여 {{ $group->female }}</td>
                            </tr>
                        </table>

                    </div>
                </div>

            </div>
        @endforeach

    </div>
</main>

</body>
</html>
