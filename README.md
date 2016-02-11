# Fuzz Image Resizer
A lightweight PHP image resizer

### Out of the box usage
1. Run `composer install`
1. Set up environment variables
1. Resize your images

### Setup
The image resizer is intended to live as a standalone microservice behind a CDN. The first request for a query combination `http://resizer-url.com/resize/?source=http://image-source.com/images/image.jpg&height=300&width=400` will fall through to the resizer instance but any subsequent requests should be cached by the CDN.

#### Environment Variables
The resizer depends on a few configurable environment variables (can be loaded from a dotenv file):
* `ALLOWED_HOSTS` - a comma separated string of whitelisted domains
* `CACHE_EXPIRATION_HOURS` - length (in hours) to set `Cache-Control` with the `max-age` directive and `Expires` headers. Any CDN should obey your cache rules and cache objects appropriately.
* `APP_ENV` - app environment

#### Healthcheck
`healthcheck.php` will return a 200 for Load Balancer health pings.

### Tests
Run `phpunit`
