# 7SG.RateLimiter
Provides a request pattern to rate limit specific routes in Flow / Neos Applications. 

Based on [gentle-force](https://github.com/mariusbalcytis/gentle-force)

## Installation
```
composer require '7sg/ratelimiter'
```

## Usage
```yaml
Neos:
  Flow:
    security:
      firewall:
        filters:
          'Seven.Website:FooBar':
            pattern: 'Seven\RateLimiter\RateLimiterRequestPattern'
            patternOptions:
              uriPattern: '/protected-route'
              redisHostname: 'REDIS_HOST'
              redisPort: 6379
              useCaseKey: 'your-use-case-key'
              maxUsages: 5
              period: 60
            interceptor: 'AccessDeny'
```
