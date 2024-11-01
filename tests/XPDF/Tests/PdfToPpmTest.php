<?php

namespace XPDF\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use XPDF\Exception\BinaryNotFoundException;
use XPDF\Exception\InvalidArgumentException;
use XPDF\PdfToPpm;

class PdfToPpmTest extends TestCase
{
    public function testBinaryNotFound()
    {
        $this->expectException(BinaryNotFoundException::class);
        PdfToPpm::create(array('pdftoppm.binaries' => '/path/to/nowhere'));
    }

    public function testGetImagesInvalidFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $pdfToPpm = PdfToPpm::create();
        $pdfToPpm->getImages('/path/to/nowhere');
    }

    public function testGetImages()
    {
        $pdfToPpm = PdfToPpm::create();

        $result = $pdfToPpm->getImages(__DIR__ . '/../../files/SearchResults.pdf');

        // ensure we have 1 page
        $this->assertEquals(2, count($result));
        foreach ($result as $file) {
            $this->assertEquals(true, file_exists($file));
            $this->assertEquals(true, preg_match('/\.ppm$/', $file));
        }

        $this->removeTempFiles($result);
    }

    public function testGetImagesOutputFormat()
    {
        $pdfToPpm = PdfToPpm::create();
        $pdfToPpm->setOutputFormat('jpeg');

        $result = $pdfToPpm->getImages(__DIR__ . '/../../files/SearchResults.pdf');
        
        // ensure we have 40 images on first page, in jpg format
        $this->assertEquals(2, count($result));
        foreach ($result as $file) {
            $this->assertEquals(true, file_exists($file));
            $this->assertEquals(true, preg_match('/\.jpg$/', $file));
        }

        $this->removeTempFiles($result);
    }

    public function testGetImagesWithStartPage()
    {
        $pdfToPpm = PdfToPpm::create();
        
        $result = $pdfToPpm->getImages(__DIR__ . '/../../files/SearchResults.pdf', 2);

        $this->assertEquals(1, count($result));
        foreach ($result as $file) {
            $this->assertEquals(true, file_exists($file));
            $this->assertEquals(true, preg_match('/\.ppm$/', $file));
        }

        $this->removeTempFiles($result);
    }

    public function testInvalidPageQuantity()
    {
        $this->expectException(InvalidArgumentException::class);
        $pdfToPpm = PdfToPpm::create();
        $pdfToPpm->setPageQuantity(0);
    }

    public function testCreate()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find PHP binary, required for this test');
        }

        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $pdfToPpm = PdfToPpm::create(array('pdftoppm.binaries' => $php, 'timeout' => 42), $logger);
        $this->assertInstanceOf('XPDF\PdfToPpm', $pdfToPpm);
        $this->assertEquals(42, $pdfToPpm->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals($logger, $pdfToPpm->getProcessRunner()->getLogger());
        $this->assertEquals($php, $pdfToPpm->getProcessBuilderFactory()->getBinary());
    }

    protected function removeTempFiles($files) {
        foreach ($files as $file) {
            if (is_writable($file)) {
                unlink($file);
            }
        }
    }
}
