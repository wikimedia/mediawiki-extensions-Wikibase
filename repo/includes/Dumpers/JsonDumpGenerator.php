<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Dumpers;

use InvalidArgumentException;
use MediaWiki\Exception\MWContentSerializationException;
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
	private int $jsonFlags = 0;
	private Serializer $entitySerializer;
	private EntityRevisionLookup $entityLookup;
	private EntityTitleStoreLookup $entityTitleStoreLookup;
	private bool $useSnippets = false;
	private bool $addPageMetadata = false;
	private JsonDataTypeInjector $dataTypeInjector;
	private AddPageInfo $addPageInfo;

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
	protected function preDump(): void {
		if ( !$this->useSnippets ) {
			$this->writeToDump( "[\n" );
		}
	}

	/**
	 * Do something after dumping data
	 */
	protected function postDump(): void {
		if ( !$this->useSnippets ) {
			$this->writeToDump( "\n]\n" );
		}
	}

	/**
	 * Do something before dumping entity
	 */
	protected function preEntityDump( int $dumpCount ): void {
		if ( $dumpCount > 0 ) {
			$this->writeToDump( ",\n" );
		}
	}

	/**
	 * @throws EntityLookupException
	 * @throws StorageException
	 */
	protected function generateDumpForEntityId( EntityId $entityId ): ?string {
		try {
			$revision = $this->entityLookup->getEntityRevision( $entityId );

			if ( !$revision ) {
				throw new EntityLookupException( $entityId, 'Entity not found: ' . $entityId->getSerialization() );
			}

		} catch ( MWContentSerializationException ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( RevisionedUnresolvedRedirectException ) {
			// Redirects aren't supposed to be in the JSON dumps
			return null;
		}

		$entity = $revision->getEntity();

		$data = $this->entitySerializer->serialize( $entity );

		$data = $this->dataTypeInjector->injectEntitySerializationWithDataTypes( $data );

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
	 * @throws StorageException
	 */
	public function encode( $data ): string {
		$json = json_encode( $data, $this->jsonFlags );

		if ( $json === false ) {
			throw new StorageException( 'Failed to encode data structure.' );
		}

		return $json;
	}

	/**
	 * @param bool $useSnippets Whether to output valid json (false) or only comma separated entities
	 */
	public function setUseSnippets( bool $useSnippets ): void {
		$this->useSnippets = $useSnippets;
	}

	/**
	 * @param bool $addPageMetadata Whether to add page metadata to entities
	 */
	public function setAddPageMetadata( bool $addPageMetadata ): void {
		$this->addPageMetadata = $addPageMetadata;
	}

	/**
	 * Flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 */
	public function setJsonFlags( int $jsonFlags ): void {
		$this->jsonFlags = $jsonFlags;
	}

	/**
	 * @see setJsonFlags
	 */
	public function getJsonFlags(): int {
		return $this->jsonFlags;
	}

}
