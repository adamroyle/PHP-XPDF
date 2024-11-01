# PHP-XPDF

PHP-XPDF is an object oriented wrapper for XPDF.

Currently available:

- PdfToText
- PdfImages
- PdfInfo
- PdfToPpm

## Installation

It is recommended to install PHP-XPDF through [Composer](http://getcomposer.org) :

```json
{
  "require": {
    "adamroyle/php-xpdf": "^1.0.0"
  }
}
```

## Dependencies :

In order to use PHP-XPDF, you need to install XPDF. Depending of your
configuration, please follow the instructions at on the
[XPDF website](http://www.foolabs.com/xpdf/download.html).

## Documentation

### Driver Initialization

The easiest way to instantiate the driver is to call the `create method.

```php
$pdfToText = XPDF\PdfToText::create();
```

You can optionaly pass a configuration and a logger (any
`Psr\Logger\LoggerInterface`).

```php
$pdfToText = XPDF\PdfToText::create(array(
    'pdftotext.binaries' => '/opt/local/xpdf/bin/pdftotext',
    'pdftotext.timeout' => 30, // timeout for the underlying process
), $logger);
```

### Extract text

To extract text from PDF, use the `getText` method.

```php
$pdfToText = XPDF\PdfToText::create();
$text = $pdfToText->getText('document.pdf');
```

You can optionally extract from a page to another page.

```php
$text = $pdfToText->getText('document.pdf', $from = 1, $to = 4);
```

You can also predefined how much pages would be extracted on any call.

```php
$pdfToText->setPageQuantity(2);
$pdfToText->getText('document.pdf'); // extracts page 1 and 2
```

### Extract embedded images

To extract embedded images from PDF, use the `PdfImages::getImages` method.

```php
$pdfImage = XPDF\PdfImage::create();
$pdfImage->setOutputFormat('jpeg');
$images = $pdfImage->getImages('document.pdf');
```

This will return an array of filenames in a temp directory.

### Generate images

To convert the entire page to an images, use the `PdfToPpm::getImages` method.

```php
$pdfToPpm = XPDF\PdfToPpm::create();
$pdfToPpm->setOutputFormat('png');

// optional, set an output resolution
$pdfToPpm->setResolution(300); // default is 150

// alternatively, set the max width/height in pixels. this overrides the resolution setting.
// $pdfToPpm->setMaxDimension(2000);

$images = $pdfToPpm->getImages('document.pdf');
```

This will return an array of filenames in a temp directory.

## License

This project is licensed under the [MIT license](http://opensource.org/licenses/MIT).
