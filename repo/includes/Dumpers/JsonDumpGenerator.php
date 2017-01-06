<?php

namespace Wikibase\Dumpers;

use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use Serializers\Serializer;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * JsonDumpGenerator generates an JSON dump of a given set of entities, excluding
 * redirects.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class JsonDumpGenerator extends DumpGenerator {

	/**
	 * @var int flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 */
	private $jsonFlags = 0;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var CallbackFactory
	 */
	private $callbackFactory;

	/**
	 * @var SerializationModifier
	 */
	private $modifier;

	/**
	 * @var bool
	 */
	private $useSnippets = false;

	/**
	 * @param resource $out
	 * @param EntityLookup $lookup Must not resolve redirects
	 * @param Serializer $entitySerializer
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$out,
		EntityLookup $lookup,
		Serializer $entitySerializer,
		EntityPrefetcher $entityPrefetcher,
		PropertyDataTypeLookup $dataTypeLookup
	) {
		parent::__construct( $out, $entityPrefetcher );
		if ( $lookup instanceof RedirectResolvingEntityLookup ) {
			throw new InvalidArgumentException( '$lookup must not resolve redirects!' );
		}

		$this->entitySerializer = $entitySerializer;
		$this->entityLookup = $lookup;
		$this->dataTypeLookup = $dataTypeLookup;

		$this->callbackFactory = new CallbackFactory();
		$this->modifier = new SerializationModifier();
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
	 * @throws EntityLookupException
	 * @throws StorageException
	 * @return string|null
	 */
	protected function generateDumpForEntityId( EntityId $entityId ) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );

			if ( !$entity ) {
				throw new EntityLookupException( $entityId, 'Entity not found: ' . $entityId->getSerialization() );
			}
		} catch ( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			// Redirects aren't supposed to be in the JSON dumps
			return null;
		}

		$data = $this->entitySerializer->serialize( $entity );

		$data = $this->injectEntitySerializationWithDataTypes( $data );

		// HACK: replace empty arrays with objects at the first level of the array
		foreach ( $data as &$element ) {
			if ( empty( $element ) ) {
				$element = new stdClass();
			}
		}

		$json = $this->encode( $data );

		return $json;
	}

	/**
	 * @param array $serialization
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function injectEntitySerializationWithDataTypes( array $serialization ) {
		$serialization = $this->modifier->modifyUsingCallback(
			$serialization,
			'claims/*/*/mainsnak',
			$this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup )
		);
		$serialization = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
			$serialization,
			'claims/*/*/qualifiers'
		);
		$serialization = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
			$serialization,
			'claims/*/*/references/*/snaks'
		);
		return $serialization;
	}

	/**
	 * @param array $array
	 * @param string $path
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function getArrayWithDataTypesInGroupedSnakListAtPath( array $array, $path ) {
		return $this->modifier->modifyUsingCallback(
			$array,
			$path,
			$this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty( $this->dataTypeLookup )
		);
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
