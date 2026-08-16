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

            /* Table navigation: filters on the left, record count and pagination on the right,
               above and below the table. */

            .table-nav {
                width: 90%;
                margin: 20px auto;
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 12px 20px;
            }

            .table-nav .pagination-nav {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 6px 12px;
                margin-left: auto;
            }

            .table-nav .count {
                font-size: 12px;
                color: #767a7a;
                white-space: nowrap;
            }

            .table-nav ul.pagination {
                margin: 0;
            }

            /* Filters. This layout loads no stylesheet, so the rules in
               resources/assets/sass/forms.scss never reach it. */

            form.external-filters {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 12px 20px;
            }

            form.external-filters .filter-field {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            form.external-filters label,
            form.external-filters .filter-label {
                font-size: 12px;
                font-weight: bold;
            }

            form.external-filters .hint {
                font-weight: normal;
                color: #767a7a;
            }

            form.external-filters input[type="text"],
            form.external-filters select {
                font: inherit;
                padding: 6px 8px;
                background: #fff;
                color: #242626;
                border: 1px solid #ccc;
                border-radius: 3px;
            }

            /* Minerals. A hover or focus panel rather than a disclosure, so opening it does not
               reflow the bar it sits in. :focus-within covers keyboard and touch, where there
               is no hover to speak of. */

            form.external-filters .mineral-filter {
                position: relative;
            }

            form.external-filters .mineral-toggle {
                font: inherit;
                padding: 6px 12px;
                background: #fff;
                color: #242626;
                border: 1px solid #ccc;
                border-radius: 3px;
                text-align: left;
                cursor: pointer;
            }

            form.external-filters .mineral-filter:hover .mineral-toggle,
            form.external-filters .mineral-filter:focus-within .mineral-toggle {
                border-color: #408077;
            }

            form.external-filters .mineral-toggle::after {
                content: ' \25BE';
                color: #767a7a;
            }

            form.external-filters .mineral-panel {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                z-index: 10;
                /* Filling down rather than across puts each rarity group in its own column:
                   R4, R8, R16, R32, R64, left to right. */
                grid-auto-flow: column;
                grid-template-rows: repeat(4, max-content);
                grid-auto-columns: max-content;
                gap: 6px 24px;
                padding: 12px 16px;
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 3px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            }

            form.external-filters .mineral-filter:hover .mineral-panel,
            form.external-filters .mineral-filter:focus-within .mineral-panel {
                display: grid;
            }

            form.external-filters .mineral-panel label {
                font-size: inherit;
                font-weight: normal;
                white-space: nowrap;
                cursor: pointer;
            }

            form.external-filters .filter-actions {
                display: flex;
                gap: 10px;
            }

            form.external-filters .filter-actions button,
            form.external-filters .reset {
                font: inherit;
                line-height: 20px;
                padding: 6px 18px;
                background: #408077;
                color: #fff;
                border: 1px solid #408077;
                border-radius: 20px;
                text-decoration: none;
                cursor: pointer;
            }

            form.external-filters .reset {
                background: none;
                color: #408077;
            }

            form.external-filters .filter-actions button:hover {
                background: #33665f;
                border-color: #33665f;
            }

            form.external-filters .reset:hover {
                background: #eef3f2;
            }

            /* Pagination. Same again, against
               resources/assets/sass/pagination.scss. Keep the two in step. */

            ul.pagination {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                list-style: none;
                justify-content: center;
                margin: 20px auto;
                padding: 0;
            }

            ul.pagination a,
            ul.pagination span {
                display: block;
                min-width: 32px;
                padding: 4px 8px;
                color: #242626;
                text-align: center;
                text-decoration: none;
                border: 1px solid #ddd;
                border-radius: 3px;
            }

            ul.pagination a:hover {
                background: #eee;
            }

            ul.pagination .disabled span {
                color: #999;
                border-color: transparent;
                cursor: not-allowed;
            }

            ul.pagination .active span {
                background: #408077;
                color: #fff;
                border-color: #408077;
                font-weight: bold;
            }

            table.moons th a {
                color: inherit;
                text-decoration: none;
            }

            table.moons th a:hover {
                text-decoration: underline;
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
