# Tinify PHP

[![Latest Stable Version](https://poser.pugx.org/devscast/tinify/version)](https://packagist.org/packages/devscast/tinify)
[![Total Downloads](https://poser.pugx.org/devscast/tinify/downloads)](https://packagist.org/packages/devscast/tinify)
[![License](https://poser.pugx.org/devscast/tinify/license)](https://packagist.org/packages/devscast/tinify)


The Tinify API allows you to compress and optimize WebP, JPEG and PNG images. It is designed as a REST service. The client libraries in various languages make it very easy to interact with the Tinify API.

## installation
You can use the PHP client by installing the Composer package and adding it to your application’s dependencies:

```bash
composer require devscast/tinify
```

## Authentication
To use the API you must provide your API key. 
You can [get an API key](https://tinypng.com/developers) by registering with your name and email address. 
Always keep your API key secret!

```php
use Devscast\Tinify\Client;

$tinify = new Client('yourtinifytoken');
```
All requests will be made over an encrypted [HTTPS](https://en.wikipedia.org/wiki/HTTPS) connection.

You can instruct the API client to make all requests over an HTTP proxy. Set the URL of your proxy server, which can optionally include credentials.

```php
use Devscast\Tinify\Client;

$tinify = new Client(
    token: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    proxy: 'http://user:pass@192.168.0.1:8080'
);
```

## Compressing Images
You can upload any WebP, JPEG or PNG image to the Tinify API to compress it. We will automatically detect the type of image and optimise with the TinyPNG or TinyJPG engine accordingly. Compression will start as soon as you upload a file or provide the URL to the image.
You can choose a local file as the source and write it to another file.

```php
<?php

use Devscast\Tinify\Client;

$tinify = new Client(token: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

$tinify->toFile(
    source: $tinify->fromFile('/home/tinify/pictures/test.png'),
    path: '/home/tinify/pictures/test-compressed.png'
);
```

You can also upload an image from a buffer (a string with binary) and get the compressed image data.

```php
<?php

$sourceBuffer = file_get_contents("unoptimized.jpg");
$compressedBuffer = $tinify->toBuffer(source: $tinify->fromBuffer($data));
```

You can provide a URL to your image instead of having to upload it.

```php
<?php

$tinify->toFile(
    source: $tinify->fromUrl('https://tinypng.com/images/panda-happy.png'),
    path: '/home/tinify/pictures/test-compressed.png'
);
```


## Resizing images
Use the API to create resized versions of your uploaded images. By letting the API handle resizing you avoid having to write such code yourself and you will only have to upload your image once. The resized images will be optimally compressed with a nice and crisp appearance.

You can also take advantage of intelligent cropping to create thumbnails that focus on the most visually important areas of your image.

Resizing counts as one additional compression. For example, if you upload a single image and retrieve the optimized version plus 2 resized versions this will count as 3 compressions in total.

```php
$tinify->toFile(
    source: $tinify->resize(
        source: $tinify->fromFile('/home/bernard-ng/Pictures/test.png'),
        method: 'fit',
        width: 500,
        height: 500
    ),
    path: '/home/bernard-ng/tinify/test-compressed-resized.png'
);
```

The method describes the way your image will be resized. The following methods are available:
* **scale**  : Scales the image down proportionally. You must provide either a target width or a target height, but not both. The scaled image will have exactly the provided width or height


* **fit** : Scales the image down proportionally so that it fits within the given dimensions. You must provide both a width and a height. The scaled image will not exceed either of these dimensions.


* **cover** Scales the image proportionally and crops it if necessary so that the result has exactly the given dimensions. You must provide both a width and a height. Which parts of the image are cropped away is determined automatically. An intelligent algorithm determines the most important areas of your image.


* **thumb** : A more advanced implementation of cover that also detects cut out images with plain backgrounds. The image is scaled down to the width and height you provide. If an image is detected with a free standing object it will add more background space where necessary or crop the unimportant parts.

If the target dimensions are larger than the original dimensions, the image will not be scaled up. Scaling up is prevented in order to protect the quality of your images.

# Preserving metadata
You can request that specific metadata is copied from the uploaded image to the compressed version. Preserving copyright information, the GPS location and the creation date are currently supported. Preserving metadata adds to the compressed file size, so you should only preserve metadata that is important to keep.

Preserving metadata will not count as an extra compression. However, in the background the image will be created again with the additional metadata.

```php
$tinify->toFile(
    source: $tinify->preserve(
        source: $tinify->fromFile('/home/bernard-ng/Pictures/test.png'),
        metadata: ['creation', 'copyright']
    ),
    path: '/home/bernard-ng/dev/projects/tinify-php/data/test-compressed.png'
);
```

You can provide the following options to preserve specific metadata. No metadata will be added if the requested metadata is not present in the uploaded image.

* **copyright** : Preserves any copyright information. This includes the EXIF copyright tag (JPEG), the XMP rights tag (PNG) as well as a Photoshop copyright flag or URL. Uses up to 90 additional bytes, plus the length of the copyright data.


* **creation** : Preserves any creation date or time. This is the moment the image or photo was originally created. This includes the EXIF original date time tag (JPEG) or the XMP creation time (PNG). Uses around 70 additional bytes.


* **location (JPEG only)** : Preserves any GPS location data that describes where the image or photo was taken. This includes the EXIF GPS latitude and GPS longitude tags (JPEG). Uses around 130 additional bytes.


## Saving to Amazon S3
You can tell the Tinify API to save compressed images directly to [Amazon S3](http://aws.amazon.com/s3/). 
If you use S3 to host your images this saves you the hassle of downloading images to your server and uploading them to S3 yourself.

```php 
$source = $tinify->toCloud(
    source: $tinify->fromFile('/home/tinify/pictures/test.png'),
    bucket_path: 'tinify/images/test.png',
    storage: new Aws(
        region: 'ap-south-1',
        secret_access_key: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        access_key_id: 'xxxxxxxxxxxxxxx',
        option: ['headers' => ['Cache-Control' => 'max-age=31536000, public']] // optional
    )
);
```
You need to provide the following options in order to save an image on Amazon S3:

* **aws_access_key_id**


* **aws_secret_access_key** : Your AWS access key ID and secret access key. These are the credentials to an Amazon AWS user account. Find out how to obtain them in Amazon’s documentation. The user must have the correct permissions, see below for details.


* **region** : The AWS region in which your S3 bucket is located.


* **path** : The path at which you want to store the image including the bucket name. The path must be supplied in the following format: ```<bucket>/<path>/<filename>```.


* **headers (experimental)** : You can add a Cache-Control header to control browser caching of the stored image, with for example: public, max-age=31536000. The full list of directives can be found in the MDN web docs.


The user that corresponds to your AWS access key ID must have the **PutObject** and **PutObjectAcl** permissions on the paths of the objects you intend to create.

## Saving to Google Cloud Storage
You can tell the Tinify API to save compressed images directly to [Google Cloud Storage](https://cloud.google.com/storage/). 
If you use GCS to host your images this saves you the hassle of downloading images to your server and uploading them to GCS yourself.

Before you can store an image in GCS you will need to generate an access token with a service account.

```php
$tinify->toCloud(
    source: $tinify->fromFile('/home/bernard-ng/Pictures/test.png'),
    bucket_path: 'tinify/images/test.png',
    storage: new Gcs(
        access_token: 'XXXXXXXXXXXXXXXXXXXXXXXXX',
        option: ['headers' => ['Cache-Control' => 'max-age=31536000, public']] // optional
    )
);
```

You need to provide the following options in order to save an image on Google Cloud Storage:

* **gcp_access_token** : The access token for authenticating to Google's Cloud Platform. Find out how to generate these tokens with the example above.


* **path** : The path at which you want to store the image including the bucket name. The path must be supplied in the following format: ```<bucket>/<path>/<filename>```.


* **headers (experimental)** : You can add a Cache-Control header to control browser caching of the stored image, with for example: public, max-age=31536000. The full list of directives can be found in the MDN web docs.

## Compression count
The API client automatically keeps track of the number of compressions you have made this month.
You can get the compression count after you have validated your API key or after you have made at least one compression request.

```php
<?php

$source = $tinify->fromUrl('https://tinypng.com/images/panda-happy.png');
$source->getCompressionCount();

$tinify->toFile($source, path: '/home/tinify/pictures/test-compressed.png');
```

## acknowledgement

this package is a reimplementation of the tinify/tinify-php library, supporting PHP 8, rewritten with a design that removes static calls for a more object-oriented approach

* [tinify/tinify-php](https://github.com/tinify/tinify-php)
* [Tinify Documentation](https://tinypng.com/developers/reference)
