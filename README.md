# Fuzz Image Resizer [![Slack Status](https://fuzz-opensource.herokuapp.com/badge.svg)](https://fuzz-opensource.herokuapp.com/)
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

#### Best Practices
In light of exploits like https://imagetragick.com/, we recommend that your implementation:  

1. Uses the latest version of ImageMagick
2. Follows mitigation procedures listed on https://imagetragick.com/
3. Has severely restricted access to any other resources (on the same network or otherwise) 
4. Only processes images from sources you whitelist

#### Healthcheck
`healthcheck.php` will return a 200 for Load Balancer health pings.

### Tests
Run `phpunit`

