<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering GlobeCoordinateValue via the Kartographer extensions.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GlobeCoordinateKartographerFormatter extends ValueFormatterBase {

	/**
	 * @var ValueFormatter
	 */
	private $coordinateFormatter;

	/**
	 * @var CachingKartographerEmbeddingHandler
	 */
	private $cachingKartographerEmbeddingHandler;

	/**
	 * @var bool
	 */
	private $emitPreviewHtml;

	/**
	 * @param FormatterOptions|null $options
	 * @param ValueFormatter $coordinateFormatter
	 * @param CachingKartographerEmbeddingHandler $cachingKartographerEmbeddingHandler
	 * @param bool $emitPreviewHtml Whether to emit HTML that can be used for live previews
	 */
	public function __construct(
		FormatterOptions $options = null,
		ValueFormatter $coordinateFormatter,
		CachingKartographerEmbeddingHandler $cachingKartographerEmbeddingHandler,
		$emitPreviewHtml
	) {
		parent::__construct( $options );

		$this->coordinateFormatter = $coordinateFormatter;
		$this->cachingKartographerEmbeddingHandler = $cachingKartographerEmbeddingHandler;
		$this->emitPreviewHtml = $emitPreviewHtml;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Generates HTML representing the details of a GlobeCoordinateValue,
	 * as an itemized list.
	 *
	 * @param GlobeCoordinateValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a GlobeCoordinateValue.' );
		}

		$html = '';

		$lang = Language::factory( $this->getOption( self::OPT_LANG ) );

		if ( !$this->emitPreviewHtml ) {
			$kartographerHtml = $this->cachingKartographerEmbeddingHandler->getHtml( $value, $lang );
		} else {
			$kartographerHtml = $this->cachingKartographerEmbeddingHandler->getPreviewHtml( $value, $lang );
		}
		if ( $kartographerHtml !== false ) {
			$html = $kartographerHtml;
		}

		$html .= Html::rawElement(
			'div',
			[ 'class' => 'wikibase-kartographer-caption' ],
			$this->coordinateFormatter->format( $value )
		);

		return "<div>$html</div>";
	}

}
