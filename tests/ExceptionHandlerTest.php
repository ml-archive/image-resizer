<?php

namespace Fuzz\ImageResizer\Tests;

use Fuzz\ImageResizer\ExceptionHandler;
use InvalidArgumentException;
use LogicException;
use Mockery;
use RuntimeException;

class ExceptionHandlerTest extends ImageResizerTestCase
{
	/**
	 * @runInSeparateProcess
	 */
	public function testItReturnsNiceErorOnInvalidArgumentException()
	{
		$exception = new InvalidArgumentException('Some message.');

		$handled = ExceptionHandler::handleException($exception);

		$this->assertEquals([
			'error'   => 'bad_request',
			'message' => $exception->getMessage(),
			'trace'   => $exception->getTraceAsString(),
		], $handled);
	}

	public function testItReturnsNiceErorOnLogicException()
	{
		$exception = new LogicException('Some message.');

		$handled = ExceptionHandler::handleException($exception);

		$this->assertEquals([
			'error'   => 'bad_request',
			'message' => $exception->getMessage(),
			'trace'   => $exception->getTraceAsString(),
		], $handled);
	}

	public function testItReturnsErrorUnknownIfInProduction()
	{
		putenv('APP_ENV=production');

		$exception = new RuntimeException('Some message.');

		$handled = ExceptionHandler::handleException($exception);

		$this->assertEquals([
			'error'   => 'unknown',
			'message' => 'Unknown Error',
			'trace'   => null,
		], $handled);
	}

	public function testItReturnsUsefulErrorIfUnknownAndInDevelopment()
	{
		putenv('APP_ENV=development');

		$exception = new RuntimeException('Some message.');

		$handled = ExceptionHandler::handleException($exception);

		$this->assertEquals([
			'error'   => $exception->getCode(),
			'message' => $exception->getMessage(),
			'trace'   => $exception->getTraceAsString(),
		], $handled);
	}
}
