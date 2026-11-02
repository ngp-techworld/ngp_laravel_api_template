<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API error messages
|--------------------------------------------------------------------------
|
| The "message" of every error response. One key per error "code"
| (see app/Exceptions/ApiExceptionRenderer.php). Keep lang/ar in sync.
|
*/

return [
    'bad_request' => 'The request is invalid.',
    'unauthenticated' => 'You need to sign in first.',
    'forbidden' => 'You are not allowed to do this.',
    'not_found' => 'The requested resource was not found.',
    'method_not_allowed' => 'This method is not allowed for this URL.',
    'conflict' => 'The request cannot be completed in the current state.',
    'csrf_token_mismatch' => 'Your session has expired. Refresh the page and try again.',
    'validation_failed' => 'The given data was invalid.',
    'too_many_requests' => 'Too many requests. Try again in :seconds seconds.',
    'server_error' => 'Something went wrong. Please try again later.',
    'service_unavailable' => 'The service is temporarily unavailable. Please try again later.',
    'http_error' => 'The request could not be completed.',
];
