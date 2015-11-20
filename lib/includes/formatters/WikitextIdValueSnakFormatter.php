<?php

namespace Wikibase\Lib\Formatters;

use DataValues\UnDeserializableValue;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyFormatterUrlLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * A formatter for PropertyValueSnaks that contain a StringValue that is interpreted as an external
 * ID. The ID is rendered as a wikitext link to an authoritative resource about the ID. The link is
 * created based on a URL pattern associated with the snak's PropertyID via a
 * PropertyFormatterUrlLookup.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikitextIdValueSnakFormatter implements SnakFormatter {

	/**
	 * @var PropertyFormatterUrlLookup
	 */
	private $formatterUrlLookup;

	/**
	 * @param PropertyFormatterUrlLookup $formatterUrlLookup
	 */
	public function __construct(
		PropertyFormatterUrlLookup $formatterUrlLookup
	) {
		$this->formatterUrlLookup = $formatterUrlLookup;
	}

	/**
	 * Formats the given Snak as an wikitext link to an authoritative resource. The link is
	 * created based on a URL pattern associated with the snak's PropertyID via the
	 * PropertyFormatterUrlLookup provided to the constructor. If no URL pattern is
	 * associated with the snak's PropertyID, the ID is returned wrapped in a span element.
	 *
	 * @param Snak $snak
	 *
	 * @throws ParameterTypeException if $snak is not a PropertyValueSnak, or if $snak->getValue()
	 * does not return a StringValue.
	 * @return string Text in the format indicated by getFormat()
	 */
	public function formatSnak( Snak $snak ) {
		Assert::parameterType( 'Wikibase\DataModel\Snak\PropertyValueSnak', $snak, '$snak' );

		/** @var PropertyValueSnak $snak  */
		$value = $snak->getDataValue();

		Assert::parameterType( 'DataValues\StringValue', $value, '$snak->getValue()' );

		// XXX: consider a SnakUrlResolver interface, which defines method resolveToUrl( Snak ).
		$urlPattern = $this->formatterUrlLookup->getUrlPatternForProperty( $snak->getPropertyId() );
		$id = $value->getValue();

		if ( $urlPattern === null ) {
			return wfEscapeWikiText( $id );
		}

		$url = str_replace( '$1', urlencode( $id ), $urlPattern );
		return '[' . $url . ' ' . wfEscapeWikiText( $id ) . ']';
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
