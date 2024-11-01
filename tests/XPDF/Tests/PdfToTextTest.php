<?php

namespace XPDF\Tests;

use PHPUnit\Framework\TestCase;
use XPDF\PdfToText;
use Symfony\Component\Process\ExecutableFinder;
use XPDF\Exception\BinaryNotFoundException;
use XPDF\Exception\InvalidArgumentException;

class PdfToTextTest extends TestCase
{
    public function testSetOutputEncoding()
    {
        $pdfToText = PdfToText::create();

        $this->assertEquals('UTF-8', $pdfToText->getOutputEncoding());
        $pdfToText->setOutputEncoding('ascii');
        $this->assertEquals('ascii', $pdfToText->getOutputEncoding());
    }

    public function testBinaryNotFound()
    {
        $this->expectException(BinaryNotFoundException::class);
        PdfToText::create(array('pdftotext.binaries' => '/path/to/nowhere'));
    }

    public function testGetTextInvalidFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $pdfToText = PdfToText::create();
        $pdfToText->getText('/path/to/nowhere');
    }

    public function testGetText()
    {
        $text = 'This is an UTF-8 encoded string : « Un éléphant ça trompe énormément ! »
It tells about elephant\'s noze !
';
        $pdfToText = PdfToText::create();
        $this->assertEquals($text, $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf'));
        $this->assertEquals($text, $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf', 1, 1));
    }

    public function testGetTextWithPageQuantity()
    {
        $text = 'This is an UTF-8 encoded string : « Un éléphant ça trompe énormément ! »
It tells about elephant\'s noze !
';
        $pdfToText = PdfToText::create();
        $pdfToText->setPageQuantity(1);
        $this->assertEquals($text, $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf'));
    }

    /**
     * @expectedException XPDF\Exception\RuntimeException
     */
    // public function testInvalidPageParams()
    // {
    //     $pdfToText = PdfToText::create();
    //     $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf', 2, 2);
    // }

    public function testInvalidPageQuantity()
    {
        $this->expectException(InvalidArgumentException::class);
        $pdfToText = PdfToText::create();
        $pdfToText->setPageQuantity(0);
    }

    public function testCreate()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find PHP binary, required for this test');
        }

        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $pdfToText = PdfToText::create(array('pdftotext.binaries' => $php, 'timeout' => 42), $logger);
        $this->assertInstanceOf('XPDF\PdfToText', $pdfToText);
        $this->assertEquals(42, $pdfToText->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals($logger, $pdfToText->getProcessRunner()->getLogger());
        $this->assertEquals($php, $pdfToText->getProcessBuilderFactory()->getBinary());
    }
}
