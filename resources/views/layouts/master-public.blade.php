<!doctype html>

<html lang="{{ app()->getLocale() }}">

    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') &#0183; Moon Mining Manager</title>

        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, “Segoe UI”, Roboto, Helvetica, Arial, sans-serif;
                font-size: 14px;
                line-height: 20px;
            }

            .logo {
                display: block;
                margin: 10px auto;
            }

            h1 {
                text-align: center;
                margin: 20px 0 40px;
            }

            h2 {
                font-size: 20px;
                font-weight: bold;
                margin: 0;
            }

            h3 {
                font-size: 16px;
                font-weight: normal;
                margin: 0;
            }

            p.center {
                text-align: center;
            }

            table {
                margin: 20px auto;
                border-collapse: collapse;
            }

            table.timers {
                width: 80%;
            }

            table.moons {
                width: 90%;
            }

            form.external-filters {
                width: 90%;
                margin: 20px auto;
            }

            form.external-filters .external-filter {
                margin-right: 20px;
            }

            th, td {
                text-align: left;
                padding: 10px;
                border: 5px solid #eee;
            }

            th {
                background: #eee;
            }

            tr.filtered {
                display: none;
            }

            .rented td {
                text-decoration: line-through;
                opacity: 0.333;
            }

            .nobreak {
                white-space: nowrap;
            }

            .timers td form label,
            .timers td form input {
                display: block;
                margin: 0 auto;
                text-align: center;
             }

            .timers .avatar {
                width: 50px;
                height: 50px;
                border-radius: 50px;
            }

            .timers .admin {
                text-align: center;
            }

            .timers .admin img {
                display: block;
                margin: 0 auto 10px;
            }

            .timers .admin a {
                display: block;
            }

            .timers tr.past td {
                opacity: 0.333;
            }

            /* Menu */

            .public-menu {
                background: #242626;
                color: #fff;
                height: 50px;
            }

            .bar .public-menu {
                margin-bottom: 100px;
            }

            .public-menu li {
                list-style: none;
            }

            .public-menu a {
                float: right;
                font-weight: bold;
                padding: 0 20px;
                color: #fff;
                line-height: 50px;
                text-decoration: none;
            }

            .public-menu a:hover,
            .public-menu a.current {
                text-decoration: none;
                background: #cad9d7;
                color: #242626;
            }

            /* Miner menu */

            .miner-bar {
                background: #242626;
                color: #fff;
                position: absolute;
                top: 50px;
                left: 0;
                height: 100px;
                width: 100%;
            }

            .miner-identity,
            .miner-amount-owed,
            .miner-total-income,
            .miner-activity-log {
                float: left;
                width: 25%;
            }

            .miner-identity {
                font-weight: bold;
                font-size: 20px;
                overflow: hidden;
                padding: 30px 0 0 110px;
            }

            .miner-identity img {
                width: 100px;
                height: 100px;
                position: absolute;
                top: 0;
                left: 0;
            }

            .miner-bar a {
                display: block;
                color: #fff;
                font-size: 12px;
                font-weight: normal;
            }

            .miner-bar a:hover {
                text-decoration: none;
                color: #cad9d7;
            }

            .miner-bar .heading {
                text-transform: uppercase;
                font-size: 12px;
                display: block;
                margin: 20px 0 10px;
            }

            .miner-bar .numeric {
                font-size: 30px;
            }

            .mining-activity {
                width: 500px;
                margin: 20px auto;
            }

            .mining-activity ul {
                list-style: none;
                padding: 0;
            }

            /* contact form */

            .contact-form form {
                margin: 0 auto;
                width: 80%;
            }

            .contact-form textarea {
                width: 100%;
            }

        </style>

    </head>

    <body class="@yield('body-class')">
        @include('common.public-nav', ['page' => $page])
        <img src="/images/logo.png" alt="Brave Collective" class="logo">

        @yield('content')

        <script src="/js/app.js"></script>
    </body>

</html>
