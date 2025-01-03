<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    
    public function render($request, Throwable $e): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        if ($e instanceof RouteNotFoundException) {
            // Redirect to a safe location, like the login page or home page
            return redirect('/login'); // Ensure this route works in your app
        }

        return parent::render($request, $e);
    }
}
