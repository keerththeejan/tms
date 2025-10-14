<?php

// Mail configuration for TMS. Update with your SMTP credentials.
return [
    'use_smtp'   => true,
    'host'       => 'smtp.gmail.com',
    // Gmail recommended: TLS on 587
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'yathunila2001@gmail.com',
    'password'   => 'zrtsswtiwjhaefyf',
    'from_email' => 'yathunila2001@gmail.com',
    'from_name'  => 'TMS',
    // Optional: set a reply-to address
    'reply_to'   => 'yathunila2001@gmail.com',
    // Reduce debug level in production (0 = off)
    'smtpDebug'  => 0,
    'debugoutput' => 'error_log',
    'auth_type'  => 'LOGIN',
    'timeout'    => 20,
  ];
