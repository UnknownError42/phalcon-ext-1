<?php

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\BaseMiddleware;

class Cors extends BaseMiddleware
{
    /** @var string */
    protected $origin;

    protected $configKey = 'cors';

    /**
     * Handle the cors.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function before(Request $request, Response $response): bool
    {
        $this->origin = $request->getHeader('Origin');

        if (!$this->isApplicable($request)) {
            return true;
        }

        if ($this->canPreflight($request)) {
            return $this->preflight($request, $response);
        }

        return $this->serve($response);
    }

    /**
     * If cors is applicable for this request.
     *
     * Not applicable if origin is empty or same as current host.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isApplicable(Request $request): bool
    {
        if (empty($this->origin)) {
            return false;
        }

        return $this->origin !== $request->getScheme() . '://' . $request->getHttpHost();
    }

    /**
     * Check if request can be served as preflight.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function canPreflight(Request $request) : bool
    {
        if (empty($request->getHeader('Access-Control-Request-Method')) ||
            $request->getMethod() !== 'OPTIONS'
        ) {
            return false;
        }

        return true;
    }

    /**
     * Handle preflight.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    protected function preflight(Request $request, Response $response): bool
    {
        if (!\in_array($request->getHeader('Access-Control-Request-Method'), $this->config['allowedMethods'])) {
            return $this->abort(405);
        }

        if (!$this->areHeadersAllowed($request->getHeader('Access-Control-Request-Headers'))) {
            return $this->abort(403);
        }

        $this->disableView();

        $response
            ->setHeader('Access-Control-Allow-Origin', $this->origin)
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setHeader('Access-Control-Allow-Methods', \implode(', ', $this->config['allowedMethods']))
            ->setHeader('Access-Control-Allow-Headers', \implode(', ', $this->config['allowedHeaders']))
            ->setHeader('Access-Control-Max-Age', $this->config['maxAge'])
            ->setContent('')
            ->send();

        return false;
    }

    /**
     * Check if cors headers from client are allowed.
     *
     * @param string|null $corsRequestHeaders
     *
     * @return bool
     */
    protected function areHeadersAllowed(string $corsRequestHeaders = null)
    {
        if ('' === \trim($corsRequestHeaders)) {
            return true;
        }

        // Normalize request headers for comparison.
        $corsRequestHeaders = \array_map(
            'strtolower',
            \explode(',', \str_replace(' ', '', $corsRequestHeaders))
        );

        return empty(\array_diff($corsRequestHeaders, $this->config['allowedHeaders']));
    }

    /**
     * Serve cors headers.
     *
     * @param Response $response
     *
     * @return bool
     */
    public function serve(Response $response): bool
    {
        if (!$this->isOriginAllowed()) {
            return $this->abort(403);
        }

        $response
            ->setHeader('Access-Control-Allow-Origin', $this->origin)
            ->setHeader('Access-Control-Allow-Credentials', 'true');

        // Optionally set expose headers.
        if ($this->config['exposedHeaders'] ?? null) {
            $response->setHeader('Access-Control-Expose-Headers', \implode(', ', $this->config['exposedHeaders']));
        }

        return true;
    }

    /**
     * If origin is white listed.
     *
     * @return bool
     */
    protected function isOriginAllowed(): bool
    {
        if (\in_array('*', $this->config['allowedOrigins'])) {
            return true;
        }

        return \in_array($this->origin, $this->config['allowedOrigins']);
    }
}
