<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
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
            //
        });

        $friendlyDbResponse = function (PDOException $e, Request $request) {
            $friendlyMessage = "We couldn't save your changes. Please try again or contact the system administrator.";

            Log::error('Database error handled safely', [
                'exception' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $friendlyMessage,
                ], 500);
            }

            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                    ->withErrors(['general' => $friendlyMessage])
                    ->with('error', $friendlyMessage);
            }

            return response()->view('errors.friendly', [
                'friendlyMessage' => "We couldn't load this page right now. Please try again or contact the system administrator.",
            ], 500);
        };

        $this->renderable(function (QueryException $e, Request $request) use ($friendlyDbResponse) {
            return $friendlyDbResponse($e, $request);
        });

        $this->renderable(function (PDOException $e, Request $request) use ($friendlyDbResponse) {
            return $friendlyDbResponse($e, $request);
        });
    }
}
