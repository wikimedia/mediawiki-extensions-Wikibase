<?php

namespace Wikibase\Lib;

use DataValues\StringValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;

/**
 * SnakUrlExpander that uses an PropertyInfoProvider to find
 * a URL pattern for expanding a Snak's value into an URL.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoSnakUrlExpander implements SnakUrlExpander {

	/**
	 * @var PropertyInfoProvider
	 */
	private $infoProvider;

	/**
	 * @param PropertyInfoProvider $infoProvider
	 */
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

		try {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$table = new PropertyInfoTable( $wikibaseRepo->getEntityIdComposer() );
			$luaFormatterProvider = new FieldPropertyInfoProvider( $table, 'luaFormatter' );
			$luaCode = $luaFormatterProvider->getPropertyInfo( $propertyId );

			$luaSandbox = new \LuaSandbox();
			$luaSandbox->registerLibrary(
				'property',
				[ 'value' => function () use ( $value ) { return [ $value->getValue() ]; } ]
			);
			$result = $luaSandbox->loadString( $luaCode, 'main' )->call();
			if ( $result[0] ) {
				return wfUrlencode($result[0]);
			}
		} catch ( \LuaSandboxError $e) {
			//ignore
			throw $e;
		}



		if ( $pattern === null ) {
			return null;
		}

		$id = wfUrlencode( $value->getValue() );
		$url = str_replace( '$1', $id, $pattern );
		return $url;
	}

}
