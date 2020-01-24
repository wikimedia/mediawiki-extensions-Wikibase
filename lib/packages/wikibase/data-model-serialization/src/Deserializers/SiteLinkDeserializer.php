<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SiteLinkDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	public function __construct( Deserializer $entityIdDeserializer ) {
		$this->entityIdDeserializer = $entityIdDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return SiteLink
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return SiteLink
	 */
	private function getDeserialized( array $serialization ) {
		return new SiteLink(
			$serialization['site'],
			$serialization['title'],
			$this->getDeserializeBadges( $serialization )
		);
	}

	private function getDeserializeBadges( array $serialization ) {
		if ( !array_key_exists( 'badges', $serialization ) ) {
			return [];
		}
		$this->assertBadgesIsArray( $serialization );

		$badges = [];
		foreach ( $serialization['badges'] as $badgeSerialization ) {
			$badges[] = $this->deserializeItemId( $badgeSerialization );
		}
		return $badges;
	}

	private function deserializeItemId( $serialization ) {
		$itemId = $this->entityIdDeserializer->deserialize( $serialization );

		if ( !( $itemId instanceof ItemId ) ) {
			throw new InvalidAttributeException(
				'badges',
				$serialization,
				"'$serialization' is not a valid item ID"
			);
		}

		return $itemId;
	}

	private function assertBadgesIsArray( $serialization ) {
		if ( !is_array( $serialization['badges'] ) ) {
			throw new InvalidAttributeException(
				'badges',
				$serialization['badges'],
				"badges attribute is not a valid array"
			);
		}
	}

	private function assertCanDeserialize( $serialization ) {
		$this->requireAttribute( $serialization, 'site' );
		$this->requireAttribute( $serialization, 'title' );
	}

	private function requireAttribute( $serialization, $attribute ) {
		if ( !is_array( $serialization ) || !array_key_exists( $attribute, $serialization ) ) {
			throw new MissingAttributeException( $attribute );
		}
	}

}
