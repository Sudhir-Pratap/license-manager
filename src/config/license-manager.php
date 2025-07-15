<?php

return [
	'license_key'    => env('LICENSE_KEY'),
	'product_id'     => env('LICENSE_PRODUCT_ID'),
	'client_id'      => env('LICENSE_CLIENT_ID'),
	'license_server' => env('LICENSE_SERVER', 'https://license.acecoderz.com'),
	'api_token'      => env('LICENSE_API_TOKEN'),
	'cache_duration' => env('LICENSE_CACHE_DURATION', 1440), // 24 hours in minutes
	'security_hash'  => env('LICENSE_SECURITY_HASH'),
];