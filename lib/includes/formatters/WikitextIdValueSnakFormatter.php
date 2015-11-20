<?php

namespace Wikibase\Lib\Formatters;

use DataValues\UnDeserializableValue;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\SnakUrlExpander;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * A formatter for PropertyValueSnaks that contain a StringValue that is interpreted as an external
 * ID. The ID is rendered as a wikitext link to an authoritative resource about the ID.
 * The URL of that link is determined using a SnakUrlExpander.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikitextIdValueSnakFormatter implements SnakFormatter {

	/**
	 * @var SnakUrlExpander
	 */
	private $urlExpander;

	/**
	 * @param SnakUrlExpander $urlExpander
	 */
	public function __construct(
		SnakUrlExpander $urlExpander
	) {
		$this->urlExpander = $urlExpander;
	}

	/**
	 * Formats the given Snak as an wikitext link to an authoritative resource.
	 * The URL of that link is determined using a SnakUrlExpander.
	 * If the snak could not be expanded into a URL, the ID is returned as simple text.
	 *
	 * @param Snak $snak
	 *
	 * @throws ParameterTypeException if $snak is not a PropertyValueSnak, or if $snak->getValue()
	 * does not return a StringValue.
	 * @return string Text in the format indicated by getFormat()
	 */
	public function formatSnak( Snak $snak ) {
		Assert::parameterType( 'Wikibase\DataModel\Snak\PropertyValueSnak', $snak, '$snak' );
		/** @var PropertyValueSnak $snak */

		$id = $snak->getDataValue()->getValue();
		$url = $this->urlExpander->expandUrl( $snak );

		if ( $url === null ) {
			return wfEscapeWikiText( $id );
		} else {
			return '[' . $url . ' ' . wfEscapeWikiText( $id ) . ']';
		}
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getFormat() {
		return SnakFormatter::FORMAT_HTML;
	}

}
