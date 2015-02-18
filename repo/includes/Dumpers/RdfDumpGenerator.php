<?php

namespace Wikibase\Dumpers;

use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\RdfSerializer;

/**
 * RdfDumpGenerator generates an RDF dump of a given set of entities, excluding
 * redirects.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class RdfDumpGenerator extends DumpGenerator {

	/**
	 *
	 * @var RdfSerializer
	 */
	private $entitySerializer;

	/**
	 *
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 *
	 * @param resource $out
	 * @param EntityLookup $lookup Must not resolve redirects
	 * @param Serializer $entitySerializer
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $out, EntityRevisionLookup $lookup, RdfSerializer $entitySerializer ) {
		parent::__construct( $out );
		if ( $lookup instanceof RedirectResolvingEntityLookup ) {
			throw new InvalidArgumentException( '$lookup must not resolve redirects!' );
		}

		$this->entitySerializer = $entitySerializer;
		$this->entityRevisionLookup = $lookup;
	}

	/**
	 * Cleanup prefixes in the dump to avoid repetitions
	 *
	 * @param string $data
	 * @return string
	 */
	protected function cleanupPrefixes( $data ) {
		return preg_replace_callback( '/@prefix .+?\n/', function ( $matches ) {
			if ( !empty( $this->prefixes[$matches[0]] ) ) {
				return '';
			}
			$this->prefixes[$matches[0]] = true;
			return $matches[0];
		}, $data );
	}

	/**
	 * Produces RDF dump of the entity
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 *
	 * @return string|null
	 */
	protected function generateDumpForEntityId( EntityId $entityId ) {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );

			if ( !$entityRevision ) {
				throw new StorageException( 'Entity not found: ' . $entityId->getSerialization() );
			}
		} catch ( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( UnresolvedRedirectException $e ) {
			return null;
		}

		$data = $this->entitySerializer->serializeEntityRevision( $entityRevision );
		return $this->cleanupPrefixes( $data );
	}
}
