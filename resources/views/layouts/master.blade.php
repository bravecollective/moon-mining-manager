<!doctype html>

<html lang="{{ app()->getLocale() }}">

    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') &#0183; Moon Mining Manager</title>

        @vite(['resources/assets/js/app.js'])

    </head>

    <body>

        <div class="container">

            <div class="navigation">
                @include('common.navigation')
            </div>

            <div class="content">
                <div class="col-12 header">
                    <div class="title">
                        @yield('title')
                    </div>
                </div>

                <div id="systemMessage" style="display: none; position: fixed; top: 80px;
                                               left: 50%; transform: translate(-50%, 0);
                                               border: 6px solid blue; padding: 15px 40px;
                                               background-color: lightgrey"></div>

                @yield('content')

            </div>

        </div>

    </body>

</html>