<?php
return [
  // Enable/disable SMS globally
  'enabled' => false,

  // Choose provider: 'twilio' or 'http'
  'provider' => 'http',

  // Common settings
  'sender' => 'TMS', // Sender name or from number depending on provider

  // Twilio settings
  'twilio' => [
    'account_sid' => '',
    'auth_token' => '',
    'from_number' => '', // E.g. +12025550123
  ],

  // Generic HTTP provider settings (e.g., Textlocal/MSG91-like)
  // Configure endpoint and parameters as required by your provider
  'http' => [
    'endpoint' => '', // e.g. https://api.textlocal.in/send/
    'method' => 'POST',
    'headers' => [
      // 'Authorization' => 'Bearer YOUR_TOKEN',
      // 'Content-Type' => 'application/x-www-form-urlencoded',
    ],
    // Map of fields the provider expects. {phone} and {message} will be replaced
    'fields' => [
      // 'apikey' => 'YOUR_API_KEY',
      // 'sender' => 'TXTLCL',
      // 'numbers' => '{phone}',
      // 'message' => '{message}',
    ],
    // Phone param name to use for substitution above
    'phone_param' => 'numbers',
    'message_param' => 'message',
  ],
];
