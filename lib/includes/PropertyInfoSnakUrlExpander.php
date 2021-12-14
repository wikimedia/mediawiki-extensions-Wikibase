<?php

namespace Wikibase\Lib;

use DataValues\StringValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoProvider;
use Wikimedia\Assert\Assert;

/**
 * SnakUrlExpander that uses an PropertyInfoProvider to find
 * a URL pattern for expanding a Snak's value into an URL.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyInfoSnakUrlExpander implements SnakUrlExpander {

	/**
	 * URL protocols (schemes) that are allowed in the pattern.
	 */
	private const ALLOWED_PROTOCOLS = [
		'https' => null,
		'http' => null,
		'tel' => null,
	];

	/**
	 * @var PropertyInfoProvider
	 */
	private $infoProvider;

	public function __construct( PropertyInfoProvider $infoProvider ) {
		$this->infoProvider = $infoProvider;
	}

	/**
	 * @see SnakUrlExpander::expandUrl
	 *
	 * @param PropertyValueSnak $snak
	 *
	 * @return string|null A URL or URI derived from the Snak, or null if no such URL
	 *         could be determined.
	 */
	public function expandUrl( PropertyValueSnak $snak ) {
		$propertyId = $snak->getPropertyId();
		$value = $snak->getDataValue();

		Assert::parameterType( StringValue::class, $value, '$snak->getDataValue()' );

		$pattern = $this->infoProvider->getPropertyInfo( $propertyId );

		if ( $pattern === null ) {
			return null;
		}

		$id = str_replace(
			'%2F', // un-encode slashes (T128078)
			'/',
			rawurlencode( $value->getValue() )
		);
		$url = str_replace( '$1', $id, $pattern );

		$parsedUrl = wfParseUrl( $url );
		if ( !$parsedUrl || !array_key_exists( $parsedUrl['scheme'], self::ALLOWED_PROTOCOLS ) ) {
			return null;
		}

		return $url;
	}

}
