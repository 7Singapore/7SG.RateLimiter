<?php
namespace Seven\RateLimiter;

use Exception;
use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\RateLimit\UsageRateLimit;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\Throttler;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;

class RateLimiterRequestPattern implements RequestPatternInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param ActionRequest $request
     * @return bool
     * @throws Exception
     */
    public function matchRequest(ActionRequest $request): bool
    {
        if (!isset($this->options['uriPattern'])) {
            throw new InvalidRequestPatternException('Missing option "uriPattern" in the Uri request pattern configuration',
                1632119747);
        }

        if ((boolean)preg_match('/^' . str_replace('/', '\/', $this->options['uriPattern']) . '$/',
            $request->getHttpRequest()->getUri()->getPath())) {
            $this->validateOptions();

            $rateLimitProvider = new RateLimitProvider();

            $rateLimitProvider->registerRateLimits(
                $this->options['useCaseKey'], [
                (new UsageRateLimit(
                    intval($this->options['maxUsages']),
                    intval($this->options['period']))
                )
            ]);

            $throttler = new Throttler(new \Predis\Client([
                'host' => $this->options['redisHostname'],
                'port' => intval($this->options['redisPort']),
            ]), $rateLimitProvider);

            $httpRequest = $request->getHttpRequest();

            if ($httpRequest->hasHeader('X-Forwarded-For')) {
                $clientIp = $httpRequest->getHeader('X-Forwarded-For')[0];
            } else {
                $clientIp = $httpRequest->getAttribute(ServerRequestAttributes::CLIENT_IP);
            }

            try {
                $throttler->checkAndIncrease($this->options['useCaseKey'], $clientIp);
            } catch (RateLimitReachedException $exception) {
                return true;
            }
        }

        return false;
    }

    protected function validateOptions()
    {
        if (!isset($this->options['redisHostname'])) {
            throw new InvalidRequestPatternException('Missing option "redisHostname" in the Uri request pattern configuration',
                1632121364);
        }

        if (!isset($this->options['redisPort'])) {
            throw new InvalidRequestPatternException('Missing option "redisPort" in the Uri request pattern configuration',
                1632121368);
        }

        if (!isset($this->options['useCaseKey'])) {
            throw new InvalidRequestPatternException('Missing option "useCaseKey" in the Uri request pattern configuration',
                1632121375);
        }

        if (!isset($this->options['maxUsages'])) {
            throw new InvalidRequestPatternException('Missing option "maxUsages" in the Uri request pattern configuration',
                1632121383);
        }

        if (!isset($this->options['period'])) {
            throw new InvalidRequestPatternException('Missing option "period" in the Uri request pattern configuration',
                1632121389);
        }
    }
}
