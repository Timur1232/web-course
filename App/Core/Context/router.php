<?php namespace App\Core\Context;
use App\Core\Helpers\Error;
use App\Core\Middleware;
use Closure;

final class Router {

    private static bool $handled = false;
    private static Request $request;

    private function __construct() {}

    public static function setup_current_request(): void {
        self::$request = Request::current();
    }

    public static function validate_path(string $p): bool {
        if ($p == '/') return true;
        return $p[0] == '/' && $p[-1] != '/';
    }

    /*
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function handle_rule(string $template_path, Closure $handler, HTTP_Method $method, array $middleware = []): bool {
        Error::assert(self::validate_path($template_path), __METHOD__.": Invalid path {$template_path}");
        if (!self::$handled && self::$request->match($template_path, $method)) {
            self::$handled = true;
            self::$request->bind_values($template_path);
            self::dispatch($handler, $middleware);
        }
        return self::$handled;
    }

    /**
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function group(string $group_path, array $middleware = []): Route_Group {
        return Route_Group::new($group_path, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function GET(string $path, Closure $handler, array $middleware = []): bool {
        return self::handle_rule($path, $handler, HTTP_Method::GET, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function POST(string $path, Closure $handler, array $middleware = []): bool {
        return self::handle_rule($path, $handler, HTTP_Method::POST, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function PUT(string $path, Closure $handler, array $middleware = []): bool {
        return self::handle_rule($path, $handler, HTTP_Method::PUT, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function PATCH(string $path, Closure $handler, array $middleware = []): bool {
        return self::handle_rule($path, $handler, HTTP_Method::PATCH, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function DELETE(string $path, Closure $handler, array $middleware = []): bool {
        return self::handle_rule($path, $handler, HTTP_Method::DELETE, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    private static function dispatch(Closure $handler, array $middleware): bool {
        // TODO: Change error handling, because this is not obvious that it will not return
        if (!self::$handled) {
            if (self::$request->method == HTTP_Method::NONE) {
                Error::method_not_allowed();
            } else {
                Error::not_found(self::$request->url->path);
            }
            return false;
        }

        Error::assert(isset($handler), __METHOD__.': No handler function');

        if (count($middleware) !== 0) {
            foreach (array_reverse($middleware) as $mw) {
                if (is_string($mw)) {
                    $mw = new $mw;
                }
                $handler = $mw->apply(self::$request, $handler);
            }
        }

        $response = $handler(self::$request);
        $comp = $response->apply();
        echo $comp->render();

        return true;
    }
}

final class Route_Group {
    /**
     * @param array<int,Middleware|class-string> $middleware
     */
    public function __construct(
        private string $group_path,
        private array $middleware = [],
    ) { }

    /**
     * @param array<int,Middleware|class-string> $middleware
     */
    public static function new(string $group_path, array $middleware = []): self {
        Error::assert(Router::validate_path($group_path), __METHOD__.": Invalid group path {$group_path}");
        return new self(
            group_path: $group_path == '/' ? '' : $group_path,
            middleware: $middleware,
        );
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public function handle_rule(string $path, Closure $handler, HTTP_Method $method, array $middleware = []): bool {
        $full_path = $this->group_path . ($path == '/' ? '' : $path);
        $full_path = $full_path == '' ? '/' : $full_path;
        return Router::handle_rule($full_path, $handler, $method, array_merge($this->middleware, $middleware));
    }

    /**
     * @param array<int,Middleware|class-string> $middleware
     */
    public function group(string $group_path, array $middleware = []): self {
        return self::new(group_path: $this->group_path . $group_path, middleware: array_merge($this->middleware, $middleware));
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public function GET(string $path, Closure $handler, array $middleware = []): bool {
        return $this->handle_rule($path, $handler, HTTP_Method::GET, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public function POST(string $path, Closure $handler, array $middleware = []): bool {
        return $this->handle_rule($path, $handler, HTTP_Method::POST, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public function PUT(string $path, Closure $handler, array $middleware = []): bool {
        return $this->handle_rule($path, $handler, HTTP_Method::PUT, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public function PATCH(string $path, Closure $handler, array $middleware = []): bool {
        return $this->handle_rule($path, $handler, HTTP_Method::PATCH, middleware: $middleware);
    }

    /**
     * @param (Closure(Request): Response) $handler
     * @param array<int,Middleware|class-string> $middleware
     */
    public function DELETE(string $path, Closure $handler, array $middleware = []): bool {
        return $this->handle_rule($path, $handler, HTTP_Method::DELETE, middleware: $middleware);
    }
}
