<?php


if (extension_loaded('imagick')) {
	echo 'pong';
} else {
	header('Content-Type: application/json', true, 501);
	exit(json_encode(['error' => 'failed to load imagemagick']));
}