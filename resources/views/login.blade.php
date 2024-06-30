<!doctype html>

<html lang="{{ app()->getLocale() }}">

    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Brave Collective - Login with EVE SSO</title>

        <link rel="stylesheet" href="css/login.css">

    </head>

    <body>

        <div class="container">
            <div class="jumbotron bg-primary text-primary">
                <div class="row justify-content-center">
                    <div class="col col-lg5">
                        <h1>Moon Mining Manager</h1>
                        <hr>
                        <p>Login with your EVE Online Account to gain access.</p>
                        <a href="/sso" role="button">
                            <img src="/images/EVE_SSO_Login_Buttons_Large_Black.png"
                                 alt="LOG IN with EVE Online" />
                        </a>
                        <p>
                            <small>
                                Learn more about
                                <a href="https://support.eveonline.com/hc/en-us/articles/205381192-Single-Sign-On-SSO"
                                   target="_blank" rel="noopener noreferrer">
                                    EVE Online Single Sign On</a>.
                            </small>
                        </p>
                    </div>
                    <div class="col col-lg4">
                        <img src="/images/logo_vector.svg" alt="Brave Collective Logo" class="img-fluid login-logo"/>
                    </div>
                </div>
            </div>
        </div>

    </body>

</html>
