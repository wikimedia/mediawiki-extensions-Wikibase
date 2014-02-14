<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\SiteLink;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SiteLinkSerializer implements Serializer {

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof SiteLink;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'SiteLinkSerializer can only serialize SiteLink objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( SiteLink $siteLink ) {
		return array(
			'site' => $siteLink->getSiteId(),
			'title' => $siteLink->getPageName(),
			'badges' => $this->serializeBadges( $siteLink->getBadges() )
		);
	}

	private function serializeBadges( array $badges ) {
		$serialization = array();

		foreach( $badges as $badge ) {
			$serialization[] = $badge->getSerialization();
		}

		return $serialization;
	}
}
