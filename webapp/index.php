<?php

/*
|--------------------------------------------------------------------------
| Shared-hosting front controller shim
|--------------------------------------------------------------------------
| On shared hosting the document root is public_html/dailynews/, which is
| the Laravel application root. This shim delegates to the real Laravel
| front controller in public/index.php, whose __DIR__-relative paths
| (../bootstrap/app.php, ../vendor/autoload.php) resolve correctly.
*/

require __DIR__.'/public/index.php';
