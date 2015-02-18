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

/**
 * JsonDumpGenerator generates an JSON dump of a given set of entities, excluding
 * redirects.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class JsonDumpGenerator extends DumpGenerator {

	/**
	 * @var int flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 */
	public $jsonFlags = 0;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var bool
	 */
	private $useSnippets = false;

	/**
	 * @param resource $out
	 * @param EntityLookup $lookup Must not resolve redirects
	 * @param Serializer $entitySerializer
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $out, EntityLookup $lookup, Serializer $entitySerializer ) {
		parent::__construct( $out );
		if ( $lookup instanceof RedirectResolvingEntityLookup ) {
			throw new InvalidArgumentException( '$lookup must not resolve redirects!' );
		}

		$this->entitySerializer = $entitySerializer;
		$this->entityLookup = $lookup;
	}

	/**
	 * Do something before dumping data
	 */
	protected function preDump() {
		if ( !$this->useSnippets ) {
			$this->writeToDump( "[\n" );
		}
	}

	/**
	 * Do something after dumping data
	 */
	protected function postDump() {
		if ( !$this->useSnippets ) {
			$this->writeToDump( "\n]\n" );
		}
	}

	/**
	 * Do something before dumping entity
	 *
	 * @param int $dumpCount
	 */
	protected function preEntityDump( $dumpCount ) {
		if ( $dumpCount > 0 ) {
			$this->writeToDump( ",\n" );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 *
	 * @return string|null
	 */
	protected function generateDumpForEntityId( EntityId $entityId ) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );

			if ( !$entity ) {
				throw new StorageException( 'Entity not found: ' . $entityId->getSerialization() );
			}
		} catch ( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( UnresolvedRedirectException $e ) {
			return null;
		}

		$data = $this->entitySerializer->getSerialized( $entity );
		$json = $this->encode( $data );

		return $json;
	}

	/**
	 * Encodes the given data as JSON
	 *
	 * @param string $data
	 *
	 * @return string
	 * @throws MWException
	 */
	public function encode( $data ) {
		$json = json_encode( $data, $this->jsonFlags );

		if ( $json === false ) {
			throw new StorageException( 'Failed to encode data structure.' );
		}

		return $json;
	}

	/**
	 * @param bool $useSnippets Whether to output valid json (false) or only comma separated entities
	 */
	public function setUseSnippets( $useSnippets ) {
		$this->useSnippets = (bool)$useSnippets;
	}

	/**
	 * Flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 *
	 * @param int $jsonFlags
	 */
	public function setJsonFlags( $jsonFlags ) {
		$this->jsonFlags = $jsonFlags;
	}

	/**
	 * @return int
	 *
	 * @see setJsonFlags
	 */
	public function getJsonFlags() {
		return $this->jsonFlags;
	}
}
