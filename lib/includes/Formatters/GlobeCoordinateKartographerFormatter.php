<?php

namespace Wikibase\Lib;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use Wikibase\Lib\CachingKartographerEmbeddingHandler;

/**
 * Formatter for rendering the details of a GlobeCoordinateValue (most useful for diffs) in HTML.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
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
	 * @param FormatterOptions|null $options
	 * @param ValueFormatter $coordinateFormatter
	 * @param CachingKartographerEmbeddingHandler $cachingKartographerEmbeddingHandler
	 */
	public function __construct(
		FormatterOptions $options = null,
		ValueFormatter $coordinateFormatter,
		CachingKartographerEmbeddingHandler $cachingKartographerEmbeddingHandler
	) {
		parent::__construct( $options );

		$this->coordinateFormatter = $coordinateFormatter;
		$this->cachingKartographerEmbeddingHandler = $cachingKartographerEmbeddingHandler;
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
		$kartographerHtml = $this->cachingKartographerEmbeddingHandler->getHtml( $value, $lang );
		if ( $kartographerHtml ) {
			$html = $kartographerHtml;
		}

		$html .= Html::rawElement(
			'div',
			// TODO: DO PROPERLY
			[ 'class' => 'wikibase-globe-coordinates', 'style' => 'clear: both' ],
			$this->coordinateFormatter->format( $value )
		);

		return "<div>$html</div>";
	}

}
