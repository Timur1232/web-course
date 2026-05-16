<?php namespace App\Core;
use App\Core\Context\Request;
use App\Core\Context\Response;
use Closure;

interface Middleware {
    /**
     * @param Closure(Request): Response $next
     * @return Closure(Request): Response
     */
    function apply(Request $req, Closure $next): Closure;
}
