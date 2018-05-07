<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use File;
use Html;
use InvalidArgumentException;
use Language;
use Linker;
use RepoGroup;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formats the StringValue from a "commonsMedia" snak as a HTML thumbnail and a link to commons.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine
 * @author Marius Hoch
 */
class CommonsInlineImageFormatter extends ValueFormatterBase {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @param FormatterOptions|null $options
	 * @param RepoGroup|null $repoGroup
	 */
	public function __construct( FormatterOptions $options = null, RepoGroup $repoGroup = null ) {
		parent::__construct( $options );

		$languageCode = $this->getOption( ValueFormatter::OPT_LANG );
		$this->language = Language::factory( $languageCode );
		$this->repoGroup = $repoGroup ?: RepoGroup::singleton();
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
			'width' => 310,
			'height' => 180
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

	/**
	 * @param Title $title
	 * @param string $thumbHtml
	 * @return string HTML
	 */
	private function wrapThumb( Title $title, $thumbHtml ) {
		$attributes = [
			'class' => 'image',
			'href' => 'https://commons.wikimedia.org/wiki/File:' . $title->getPartialURL()
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
			'href' => 'https://commons.wikimedia.org/wiki/File:' . $title->getPartialURL()
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
			htmlspecialchars( $this->language->formatSize( $file->getSize() ) )
		] );
	}

}
