<?php

namespace Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 * @author Adam Shorland
 */
class FingerprintSerializer {

	/**
	 * @since 1.5
	 */
	const USE_OBJECTS_FOR_MAPS = true;

	/**
	 * @since 1.5
	 */
	const USE_ARRAYS_FOR_MAPS = false;

	/**
	 * @var bool
	 */
	private $useObjectsForMaps;

	/**
	 * @var TermListSerializer
	 */
	private $termListSerializer;

	/**
	 * @var AliasGroupSerializer
	 */
	private $aliasGroupSerializer;

	/**
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( $useObjectsForMaps ) {
		$this->useObjectsForMaps = $useObjectsForMaps;

		$this->termListSerializer = new TermListSerializer(
			new TermSerializer(),
			$useObjectsForMaps
		);
		$this->aliasGroupSerializer = new AliasGroupSerializer();
	}

	public function addBasicsToSerialization( EntityId $id = null, Fingerprint $fingerprint, array &$serialization ) {
		$this->addIdToSerialization( $id, $serialization );

		$serialization['labels'] = $this->termListSerializer->serialize( $fingerprint->getLabels() );
		$serialization['descriptions'] = $this->termListSerializer->serialize( $fingerprint->getDescriptions() );
		$serialization['aliases'] = $this->serializeAliasGroupList( $fingerprint->getAliasGroups() );
	}

	private function addIdToSerialization( EntityId $id = null, array &$serialization ) {
		if ( $id === null ) {
			return;
		}

		$serialization['id'] = $id->getSerialization();
	}

	/**
	 * @deprecated this is used somewhere stupid...
	 */
	public function addDescriptionsToSerialization( TermList $descriptions, array &$serialization ) {
		$serialization['descriptions'] = $this->termListSerializer->serialize( $descriptions );
	}

	/**
	 * @deprecated this is used somewhere stupid...
	 */
	public function addAliasesToSerialization( AliasGroupList $aliases, array &$serialization ) {
		$serialization['aliases'] = $this->serializeAliasGroupList( $aliases );
	}

	private function serializeAliasGroupList( AliasGroupList $aliases ) {
		$serialization = array();

		foreach ( $aliases as $aliasGroup ) {
			$serialization = array_merge(
				$serialization,
				$this->aliasGroupSerializer->serialize( $aliasGroup )
			);
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

}
