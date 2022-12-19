<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakUrlExpander;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterTypeException;

/**
 * A formatter for PropertyValueSnaks that contain a StringValue that is
 * interpreted as an external identifier. The identifier is rendered as an wikitext link to some
 * resource about the wikitext. The link is created based on a URL pattern associated with the
 * snak's property id via a SnakUrlExpander.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikitextExternalIdentifierFormatter implements SnakFormatter {

	/**
	 * @var SnakUrlExpander
	 */
	private $urlExpander;

	public function __construct( SnakUrlExpander $urlExpander ) {
		$this->urlExpander = $urlExpander;
	}

	private function escapeWikitextInUrl( $url ) {
		return str_replace( [
			'[', ']',     '<', '>',     '\'',  ' ', "\r", "\n", "\t",
		], [
			'%5B', '%5D', '%3C', '%3E', '%27', '%20', '%0D', '%0A', '%09',
		], $url );
	}

	/**
	 * @see SnakFormatter::format
	 *
	 * Formats the given Snak as an wikitext link to an authoritative resource.
	 * The URL of that link is determined using a SnakUrlExpander.
	 * If the snak could not be expanded into a URL, the identifier is returned as simple text.
	 *
	 * @param Snak $snak
	 *
	 * @throws ParameterTypeException if $snak is not a PropertyValueSnak, or if $snak->getDataValue()
	 * does not return a StringValue.
	 * @return string Wikitext
	 */
	public function formatSnak( Snak $snak ) {
		Assert::parameterType( PropertyValueSnak::class, $snak, '$snak' );
		/** @var PropertyValueSnak $snak */
		'@phan-var PropertyValueSnak $snak';

		$id = $snak->getDataValue()->getValue();
		$url = $this->urlExpander->expandUrl( $snak );

		if ( $url === null ) {
			return wfEscapeWikiText( $id );
		} else {
			return '[' . $this->escapeWikitextInUrl( $url ) . ' ' . wfEscapeWikiText( $id ) . ']';
		}
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string SnakFormatter::FORMAT_WIKI
	 */
	public function getFormat() {
		return SnakFormatter::FORMAT_WIKI;
	}

}
