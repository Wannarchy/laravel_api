<?php

return [

    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost/Cyna_front/public'), '/'),

    'email_verification_expire_hours' => (int) env('EMAIL_VERIFICATION_EXPIRE_HOURS', 24),

    'admin_otp_expire_minutes' => (int) env('ADMIN_OTP_EXPIRE_MINUTES', 15),

    'admin_otp_max_attempts' => (int) env('ADMIN_OTP_MAX_ATTEMPTS', 5),

];
