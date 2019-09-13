<?php

namespace MemeGenerator\Command;

use Imagick;
use ImagickDraw;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class GenerateMemeCommand.
 */
class GenerateMemeCommand extends Command
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('meme:generate')
            ->setAliases([static::class])
            ->addArgument('image', InputArgument::REQUIRED, 'Image to put the text on.')
            ->addArgument('top-text', InputArgument::REQUIRED, 'Text to add on the top.')
            ->addArgument('bottom-text', InputArgument::OPTIONAL, 'Text to add on the bottom.')
            ->addOption('output-file', 'o', InputOption::VALUE_OPTIONAL, 'The name of the output file', 'meme.jpeg')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $image = new Imagick($input->getArgument('image'));

        $draw = new ImagickDraw();
        $draw->setFillColor('white');
        $draw->setFont('Arial');
        $draw->setFontSize(floor($image->getImageHeight() * 0.05));
        $draw->setFontWeight(900);
        $draw->setStrokeColor('black');
        $draw->setStrokeWidth(floor($image->getImageHeight() * 0.001));
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);

        $textTop = $this->textImage(
            strtoupper($input->getArgument('top-text')),
            $draw,
            $image->getImageWidth()
        );

        $image->compositeImage(
            $textTop,
            Imagick::COMPOSITE_OVER,
            ($image->getImageWidth() - $textTop->getImageWidth()) / 2,
            ($image->getImageHeight() * 0.05)
        );

        if ($input->hasArgument('bottom-text') && $input->getArgument('bottom-text')) {
            $textBottom = $this->textImage(
                strtoupper($input->getArgument('bottom-text')),
                $draw,
                $image->getImageWidth()
            );

            $image->compositeImage(
                $textBottom,
                Imagick::COMPOSITE_OVER,
                ($image->getImageWidth() - $textBottom->getImageWidth()) / 2,
                ($image->getImageHeight() * 0.95) - $textBottom->getImageHeight()
            );
        }

        $image->writeImage($input->getOption('output-file'));
        $io->success('Written image to ' . realpath($input->getOption('output-file')));
    }

    /**
     * Create a text image.
     *
     * @param string $text
     * @param \ImagickDraw $draw
     * @param int $width
     *
     * @return \Imagick
     */
    protected function textImage($text, ImagickDraw $draw, int $width)
    {
        $textImage = new Imagick();
        list($lines, $lineHeight) = $this->wordWrapAnnotation($textImage, $draw, $text, $width);
        $textImage->newImage($width, count($lines) * $lineHeight, "transparent");

        for ($i = 0; $i < count($lines); $i++) {
            $metrics = $textImage->queryFontMetrics($draw, $lines[$i]);
            $textImage->annotateImage(
                $draw,
                0,
                $metrics['ascender'] + $metrics['descender'] + $i * $lineHeight + $draw->getStrokeWidth(),
                0,
                $lines[$i]
            );
        }

        $textImage->trimImage(0);

        return $textImage;
    }

    /**
     * Wordwrap an annotation to a width.
     *
     * @param \Imagick $image
     * @param \ImagickDraw $draw
     * @param string $text
     * @param int $maxWidth
     *
     * @return array
     */
    protected function wordWrapAnnotation(Imagick $image, ImagickDraw $draw, string $text, int $maxWidth)
    {
        $words = preg_split('%\s%', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lines = array();
        $i = 0;
        $lineHeight = 0;

        while (count($words) > 0) {
            $metrics = $image->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
            $lineHeight = max($metrics['textHeight'], $lineHeight);

            if ($metrics['textWidth'] > $maxWidth || count($words) < $i) {
                $lines[] = implode(' ', array_slice($words, 0, --$i));
                $words = array_slice($words, $i);
                $i = 0;
            }
        }

        return array($lines, $lineHeight);
    }
}
