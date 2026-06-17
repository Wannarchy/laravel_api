<?php

return [

    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost/Cyna_front/public'), '/'),

    'email_verification_expire_hours' => (int) env('EMAIL_VERIFICATION_EXPIRE_HOURS', 24),

];
