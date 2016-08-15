<?php

namespace Fuzz\ImageResizer;

use Exception;
use InvalidArgumentException;
use LogicException;

class ExceptionHandler
{
	public static function handleException(Exception $exception)
	{
		http_response_code(400);
		if ($exception instanceof InvalidArgumentException || $exception instanceof LogicException) {
			return [
				'error'   => 'bad_request',
				'message' => $exception->getMessage(),
				'trace'   => $exception->getTraceAsString(),
			];
		}

		if (getenv('APP_ENV') === 'production') {
			return [
				'error'   => 'unknown',
				'message' => 'Unknown Error',
				'trace'   => null,
			];
		} else {
			return [
				'error'   => $exception->getCode(),
				'message' => $exception->getMessage(),
				'trace'   => $exception->getTraceAsString(),
			];
		}
	}
}
