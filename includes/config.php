<?php
// TODO: DB settings - for a repo, prefer to use env vars in production.
// Update these for your local environment.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cookie_consent');
define('DB_USER', 'root');
define('DB_PASS', 'FTXNNE^t&VFHf!6B2HCybmp*YtMoYyR');

// Cookie settings
define('CONSENT_COOKIE_NAME', 'consent_cookie');
define('CONSENT_COOKIE_VERSION', 1);
define('CONSENT_COOKIE_EXPIRE_YEARS', 1);

// Timezone - Important for reproducible datetime
date_default_timezone_set('Asia/Kuala_Lumpur');
