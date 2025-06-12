<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof PostTooLargeException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file exceeds the 2 MB size limit.',
                ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }

            return redirect()->back()
                ->withErrors(['avatar' => 'The uploaded file exceeds the 2 MB size limit.'])
                ->withInput();
        }

        return parent::render($request, $exception);
    }
}
