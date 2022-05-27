<?php

namespace Wikibase\Repo\Dumpers;

use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\AddPageInfo;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * JsonDumpGenerator generates an JSON dump of a given set of entities, excluding
 * redirects.
 *
 * @license GPL-2.0-or-later
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
	 * @var EntityRevisionLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

	/**
	 * @var bool
	 */
	private $useSnippets = false;

	/**
	 * @var bool
	 */
	private $addPageMetadata = false;

	/**
	 * @var JsonDataTypeInjector
	 */
	private $dataTypeInjector;

	/**
	 * @var AddPageInfo
	 */
	private $addPageInfo;

	/**
	 * @param resource $out
	 * @param EntityRevisionLookup $lookup
	 * @param Serializer $entitySerializer
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityIdParser $entityIdParser
	 * @param EntityTitleStoreLookup $entityTitleStoreLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$out,
		EntityRevisionLookup $lookup,
		Serializer $entitySerializer,
		EntityPrefetcher $entityPrefetcher,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser,
		EntityTitleStoreLookup $entityTitleStoreLookup
	) {
		parent::__construct( $out, $entityPrefetcher );

		$this->entitySerializer = $entitySerializer;
		$this->entityLookup = $lookup;
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;

		$this->dataTypeInjector = new JsonDataTypeInjector(
			new SerializationModifier(),
			new CallbackFactory(),
			$dataTypeLookup,
			$entityIdParser
		);

		$this->addPageInfo = new AddPageInfo( $this->entityTitleStoreLookup );
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
			$revision = $this->entityLookup->getEntityRevision( $entityId );

			if ( !$revision ) {
				throw new EntityLookupException( $entityId, 'Entity not found: ' . $entityId->getSerialization() );
			}

		} catch ( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			// Redirects aren't supposed to be in the JSON dumps
			return null;
		}

		$entity = $revision->getEntity();

		$data = $this->entitySerializer->serialize( $entity );

		$data = $this->dataTypeInjector->injectEntitySerializationWithDataTypes( $data );

		// HACK: replace empty arrays with objects at the first level of the array
		foreach ( $data as &$element ) {
			if ( empty( $element ) ) {
				$element = (object)[];
			}
		}

		if ( $this->addPageMetadata ) {
			$data = $this->addPageInfo->add( $data, $revision );
		} else {
			$data['lastrevid'] = $revision->getRevisionId();
		}

		$json = $this->encode( $data );

		return $json;
	}

	/**
	 * Encodes the given data as JSON
	 *
	 * @param mixed $data
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
	 * @param bool $addPageMetadata Whether to add page metadata to entities
	 */
	public function setAddPageMetadata( $addPageMetadata ) {
		$this->addPageMetadata = (bool)$addPageMetadata;
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
