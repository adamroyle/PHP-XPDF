<?php

/*
 * This file is part of PHP-XPDF.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XPDF;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use XPDF\Exception\InvalidArgumentException;
use XPDF\Exception\RuntimeException;
use XPDF\Exception\BinaryNotFoundException;

/**
 * The PdfToPpm object.
 *
 * This binary adapter is used to rasterize PDF pages as images with the PdfToPpp
 * binary provided by XPDF.
 *
 * @license MIT
 */
class PdfToPpm extends AbstractBinary
{
    private $pages;
    private $output_format = '';
    private $resolution = 0;
    private $maxDimension = 0;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdftoppm';
    }

    /**
     * Sets a quantity of page to extract by default
     *
     * @param integer $pages
     *
     * @return PdfToPpm
     */
    public function setPageQuantity($pages)
    {
        if (0 >= $pages) {
            throw new InvalidArgumentException('Page quantity must be a positive value');
        }

        $this->pages = $pages;

        return $this;
    }

    /**
     * Set the image output format.
     *
     * Normally, all images are written as PPM. This option allows
     * images in to be saved as JPEG, PNG, or TIFF.
     *  
     * @param string $format  The output format, one of "png", "jpeg", "jpegcmyk" or "tiff"
     * 
     * @return PdfToPpm
     */
    public function setOutputFormat($format)
    {
        switch (strtolower($format)) {
            case 'jpeg':
            case 'jpg':
                $this->output_format = '-jpeg';
                break;
            case 'jpegcmyk':
                $this->output_format = '-jpegcmyk';
                break;
            case 'png':
                $this->output_format = '-png';
                break;
            case 'tiff':
                $this->output_format = '-tiff';
                break;
            case 'ppm':
                $this->output_format = '';
                break;
            default:
                throw new InvalidArgumentException('Format must be one of "png", "jpeg", "jpegcmyk" or "tiff"');
                break;
        }

        return $this;
    }

    /**
     * Sets the resolution resolution of the output image, as a DPI value.
     * Overrides the max dimension setting.
     *
     * @param integer $resolution The resolution in DPI
     * @return PdfToPpm
     */
    public function setResolution(int $resolution)
    {
        $this->resolution = $resolution;
        $this->maxDimension = 0;

        return $this;
    }

    /**
     * Sets the maximum width or height of the output image, in pixels.
     * Overrides the resolution setting.
     *
     * @param integer $width
     * @return PdfToPpm
     */
    public function setMaxDimension(int $length)
    {
        $this->maxDimension = $length;
        $this->resolution = 0;

        return $this;
    }

    /**
     * Generates images from the current open PDF file, if not page start/end
     * provided, generate all pages.
     *
     * Image files will end in .ppm, and placed in the 
     * system temp directory.
     * 
     * To save images as a different filetype, use:
     *     $pdfToPpm->setOutputFormat('png');
     *     $pdfToPpm->setOutputFormat('jpeg');
     *     $pdfToPpm->setOutputFormat('jpegcmyk');
     *     $pdfToPpm->setOutputFormat('tiff');
     *
     * @param string  $pathfile   The path to the PDF file
     * @param integer $page_start The starting page number (first is 1)
     * @param integer $page_end   The ending page number
     *
     * @return array File paths of the extracted images
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getImages($pathfile, $page_start = null, $page_end = null)
    {
        if ( ! file_exists($pathfile)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid file', $pathfile));
        }

        $commands = array();

        if (null !== $page_start) {
            $commands[] = '-f';
            $commands[] = (int) $page_start;
        }
        if (null !== $page_end) {
            $commands[] = '-l';
            $commands[] = (int) $page_end;
        } elseif (null !== $this->pages) {
            $commands[] = '-l';
            $commands[] = (int) $page_start + $this->pages;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'xpdf');

        if ($this->output_format) {
            $commands[] = $this->output_format;
        }

        if ($this->resolution) {
            $commands[] = '-r';
            $commands[] = $this->resolution;
        }

        if ($this->maxDimension) {
            $commands[] = '-scale-to';
            $commands[] = $this->maxDimension;
        }

        $commands[] = $pathfile;
        $commands[] = $tmpFile;

        try {
            $this->command($commands);
            
            if (is_writable($tmpFile)) {
                unlink($tmpFile);
            }

            $ret = glob($tmpFile . '*');

        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Unable to extract images', $e->getCode(), $e);
        }

        return $ret;
    }

    /**
     * Factory for PdfToPpm
     *
     * @param array|Configuration $configuration
     * @param LoggerInterface     $logger
     *
     * @return PdfToPpm
     */
    public static function create($configuration = array(), LoggerInterface $logger = null)
    {
        if (!$configuration instanceof ConfigurationInterface) {
            $configuration = new Configuration($configuration);
        }

        $binaries = $configuration->get('pdftoppm.binaries', 'pdftoppm');

        if (!$configuration->has('timeout')) {
            $configuration->set('timeout', 60);
        }

        try {
            return static::load($binaries, $logger, $configuration);
        } catch (ExecutableNotFoundException $e) {
            throw new BinaryNotFoundException('Unable to find pdftoppm', $e->getCode(), $e);
        }
    }
}
