<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use File;
use Html;
use InvalidArgumentException;
use Language;
use Linker;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use ParserOptions;
use RepoGroup;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a "commonsMedia" snak as a HTML thumbnail and a link to commons.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine
 * @author Marius Hoch
 */
class CommonsInlineImageFormatter implements ValueFormatter {

	private const FALLBACK_THUMBNAIL_WIDTH = 320; // 320 the was default hardcoded value. Removed in T224189

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @var ParserOptions
	 */
	private $parserOptions;

	/**
	 * @var array
	 */
	private $thumbLimits;

	/**
	 * @var FormatterOptions
	 */
	private $options;

	/**
	 * @param ParserOptions $parserOptions Options for thumbnail size
	 * @param array $thumbLimits Mapping of thumb number to the limit like [ 0 => 120, 1 => 240, ...]
	 * @param LanguageFactory $languageFactory
	 * @param FormatterOptions|null $options
	 * @param RepoGroup|null $repoGroup
	 * @throws \MWException
	 */
	public function __construct(
		ParserOptions $parserOptions,
		array $thumbLimits,
		LanguageFactory $languageFactory,
		FormatterOptions $options = null,
		RepoGroup $repoGroup = null
	) {
		$this->options = $options ?: new FormatterOptions();

		$languageCode = $this->options->getOption( ValueFormatter::OPT_LANG );
		$this->language = $languageFactory->getLanguage( $languageCode );
		$this->repoGroup = $repoGroup ?: MediaWikiServices::getInstance()->getRepoGroup();
		$this->parserOptions = $parserOptions;
		$this->thumbLimits = $thumbLimits;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given commons file name as an HTML image gallery.
	 *
	 * @param StringValue $value The commons file name
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$fileName = $value->getValue();
		// We cannot use makeTitle because it does not secureAndSplit()
		$title = Title::makeTitleSafe( NS_FILE, $fileName );
		if ( $title === null ) {
			return htmlspecialchars( $fileName );
		}

		$transformOptions = [
			'width' => $this->getThumbWidth( $this->parserOptions->getThumbSize() ),
			'height' => 1000,
		];

		$file = $this->repoGroup->findFile( $fileName );
		if ( !$file instanceof File ) {
			return $this->getCaptionHtml( $title );
		}
		$thumb = $file->transform( $transformOptions );
		if ( !$thumb ) {
			return $this->getCaptionHtml( $title );
		}

		Linker::processResponsiveImages( $file, $thumb, $transformOptions );

		return $this->wrapThumb( $title, $thumb->toHtml() ) . $this->getCaptionHtml( $title, $file );
	}

	private function getThumbWidth( $thumbSize ) {
		return $this->thumbLimits[$thumbSize] ?? self::FALLBACK_THUMBNAIL_WIDTH;
	}

	/**
	 * @param Title $title
	 * @param string $thumbHtml
	 * @return string HTML
	 */
	private function wrapThumb( Title $title, $thumbHtml ) {
		$attributes = [
			'class' => 'image',
			'href' => 'https://commons.wikimedia.org/wiki/File:' . $title->getPartialURL(),
		];

		return Html::rawElement(
			'div',
			[ 'class' => 'thumb' ],
			Html::rawElement( 'a', $attributes, $thumbHtml )
		);
	}

	/**
	 * @param Title $title
	 * @param File|null $file
	 * @return string HTML
	 */
	private function getCaptionHtml( Title $title, $file = null ) {
		$attributes = [
			'href' => 'https://commons.wikimedia.org/wiki/File:' . $title->getPartialURL(),
		];
		$innerHtml = Html::element( 'a', $attributes, $title->getText() );

		if ( $file ) {
			$innerHtml .= Html::element( 'br' ) . $this->getFileMetaHtml( $file );
		}

		return Html::rawElement(
			'div',
			[ 'class' => 'commons-media-caption' ],
			$innerHtml
		);
	}

	/**
	 * @param File $file
	 * @return string HTML
	 */
	private function getFileMetaHtml( File $file ) {
		return $this->language->semicolonList( [
			$file->getDimensionsString(),
			htmlspecialchars( $this->language->formatSize( $file->getSize() ) ),
		] );
	}

}
