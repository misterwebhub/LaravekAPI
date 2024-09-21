<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //code
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //code
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException && $request->wantsJson()) {
            return response()->json([
                'message' => trans('admin.resource_not_found')
            ], 404);
        }

        if ($exception instanceof ValidationException) {
            return response()->json(
                [
                    'message' => trans('admin.data_invalid'),
                    'errors' => $exception->validator->getMessageBag()
                ],
                422
            );
        }

        if ($exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
            return response()->json(['message' => trans('admin.forbidden_message')], 403);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json(['message' => trans('admin.forbidden_message')], 403);
        }

        return parent::render($request, $exception);
    }
}
