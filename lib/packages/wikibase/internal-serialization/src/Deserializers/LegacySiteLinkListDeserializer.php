<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySiteLinkListDeserializer implements Deserializer {

	/**
	 * @param array $serialization
	 *
	 * @return SiteLink[]
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertStructureIsValid( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function assertStructureIsValid( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'SiteLink list serializations should be arrays' );
		}

		foreach ( $serialization as $key => $arrayElement ) {
			$this->assertKeyIsValid( $key );
			$this->assertElementIsValid( $arrayElement );
		}
	}

	private function assertKeyIsValid( $key ) {
		if ( !is_string( $key ) ) {
			throw new DeserializationException( 'All array keys should be strings' );
		}
	}

	private function assertElementIsValid( $arrayElement ) {
		if ( !is_string( $arrayElement ) && !is_array( $arrayElement ) ) {
			throw new DeserializationException( 'All array elements should be of type string or array' );
		}

		if ( is_array( $arrayElement ) ) {
			$this->assertElementIsValidArray( $arrayElement );
		}
	}

	private function assertElementIsValidArray( array $arrayElement ) {
		if ( !array_key_exists( 'name', $arrayElement ) ) {
			throw new MissingAttributeException( 'name' );
		}

		if ( !array_key_exists( 'badges', $arrayElement ) ) {
			throw new MissingAttributeException( 'badges' );
		}
	}

	/**
	 * @param array $siteLinkArray
	 *
	 * @return SiteLinkList
	 */
	private function getDeserialized( array $siteLinkArray ) {
		$siteLinks = [];

		foreach ( $siteLinkArray as $siteId => $siteLinkData ) {
			$siteLinks[] = $this->newSiteLinkFromSerialization( $siteId, $siteLinkData );
		}

		return new SiteLinkList( $siteLinks );
	}

	/**
	 * @param string $siteId
	 * @param string|array $siteLinkData
	 *
	 * @throws DeserializationException
	 * @return SiteLink
	 */
	private function newSiteLinkFromSerialization( $siteId, $siteLinkData ) {
		try {
			return $this->tryNewSiteLinkFromSerialization( $siteId, $siteLinkData );
		} catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException( $ex->getMessage(), $ex );
		}
	}

	/**
	 * @param string $siteId
	 * @param string|array $siteLinkData
	 *
	 * @return SiteLink
	 */
	private function tryNewSiteLinkFromSerialization( $siteId, $siteLinkData ) {
		if ( is_array( $siteLinkData ) ) {
			$pageName = $siteLinkData['name'];
			$badges = $this->getDeserializedBadges( $siteLinkData['badges'] );
		} else {
			$pageName = $siteLinkData;
			$badges = [];
		}

		return new SiteLink( $siteId, $pageName, $badges );
	}

	/**
	 * @param string[] $badgesSerialization
	 *
	 * @return ItemId[]
	 */
	private function getDeserializedBadges( array $badgesSerialization ) {
		$badges = [];

		foreach ( $badgesSerialization as $badgeSerialization ) {
			$badges[] = new ItemId( $badgeSerialization );
		}

		return $badges;
	}

}
