<?php

// @TODO this is ugly but working... Needs some TLC...

require __DIR__ . '/vendor/autoload.php';

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$src = $request->getPathInfo();

if ($request->get('src')) {
	$src = $request->get('src');
}

$path = $request->getPathInfo();

$config = [
	'height' => $request->get('height'),
	'width' => $request->get('width'),
	'crop' => (bool) $request->get('crop'),
	'src' => $request->getPathInfo(),
];

// Check proper src
if ($request->get('src')) {
	$config['src'] = $request->get('src');
}


// Setup file
$file = \Fuzz\ImageResizer\File::createFromBlob(file_get_contents($src));
// Set proper headers afterwards.
header('Content-Type: ' . $file->getMimeType());
//header('Content-Length: ' . $file->getSize()); This wasn't returning the proper length... @todo needs to be fixed.

// Set time for cache expires @todo this should be a config setting. PS: This code is ugly :(.
$gmdate_expires = gmdate ('D, d M Y H:i:s', strtotime ('now +120  days')) . ' GMT';
$gmdate_modified = gmdate ('D, d M Y H:i:s') . ' GMT';
header('Last-Modified: ' . $gmdate_modified);
header('Cache-Control: max-age=10368000, must-revalidate'); //120 days
header('Expires: ' . $gmdate_expires);

$image = new \Fuzz\ImageResizer\Resizer($file);

$rawImage = $image->resizeImage($config);

echo $rawImage;
exit;