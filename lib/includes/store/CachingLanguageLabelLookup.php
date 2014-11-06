<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class CachingLanguageLabelLookup implements LabelLookup {

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param EntityLookup $entityLookup
	 * @param string $languageCode
	 */
	public function __construct( EntityLookup $entityLookup, $languageCode ) {
		$this->entityLookup = $entityLookup;
		$this->languageCode = $languageCode;
	}

	/**
	 * Lookup a label for an entity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException If an entity with that ID could not be loaded.
	 * @return string
	 */
	public function getLabel( EntityId $entityId ) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );
		} catch ( UnresolvedRedirectException $ex )  {
			$entity = null;
		} catch ( StorageException $ex )  {
			$entity = null;
			wfLogWarning( 'Failed to load entity: '
				. $entityId->getSerialization() . ': '
				. $ex->getMessage() );
		}

		if ( $entity === null ) {
			// double redirect, deleted entity, etc
			throw new OutOfBoundsException( "An Entity with the id $entityId could not be loaded" );
		}

		return $entity->getLabel( $languageCode );
	}

}
