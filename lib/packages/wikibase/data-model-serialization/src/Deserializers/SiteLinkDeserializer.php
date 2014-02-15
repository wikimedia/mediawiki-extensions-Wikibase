<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\SiteLink;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SiteLinkDeserializer implements Deserializer {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return is_array( $serialization ) &&
			array_key_exists( 'site', $serialization ) &&
			array_key_exists( 'title', $serialization );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		return new SiteLink(
			$serialization['site'],
			$serialization['title'],
			$this->getDeserializeBadges( $serialization )
		);
	}

	private function getDeserializeBadges( array $serialization ) {
		if ( !array_key_exists( 'badges', $serialization ) ) {
			return array();
		}
		$this->assertBadgesIsArray( $serialization );

		$badges = array();
		foreach( $serialization['badges'] as $badgeSerialization ) {
			$badges[] = $this->parseItemId( $badgeSerialization );
		}
		return $badges;
	}

	private function parseItemId( $itemId ) {
		try {
			return $this->entityIdParser->parse( $itemId );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidAttributeException(
				'badges',
				$itemId,
				"'$itemId' is not a valid item ID",
				$e
			);
		}
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
