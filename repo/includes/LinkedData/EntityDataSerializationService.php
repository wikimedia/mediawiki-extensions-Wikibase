<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\LinkedData;

use InvalidArgumentException;
use MediaWiki\Json\FormatJson;
use RuntimeException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Repo\AddPageInfo;
use Wikibase\Repo\Dumpers\JsonDataTypeInjector;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\RdfBuilder;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\UnknownFlavorException;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * Service for serializing entity data.
 *
 * Note that we are using the API's serialization facility to ensure a consistent external
 * representation of data entities. Using the ContentHandler to serialize the entity would expose
 * internal implementation details.
 *
 * For RDF output, this relies on the RdfBuilder class.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class EntityDataSerializationService {

	/**
	 * @var Serializer
	 */
	private $serializer;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var RdfWriterFactory
	 */
	private $rdfWriterFactory;

	/**
	 * @var RdfBuilderFactory
	 */
	private $rdfBuilderFactory;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

	/**
	 * @var JsonDataTypeInjector
	 */
	private $dataTypeInjector;

	public function __construct(
		Serializer $serializer,
		EntityDataFormatProvider $entityDataFormatProvider,
		RdfBuilderFactory $rdfBuilderFactory,
		EntityTitleStoreLookup $entityTitleStoreLookup,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser,
	) {
		$this->serializer = $serializer;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->rdfBuilderFactory = $rdfBuilderFactory;
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;

		$this->dataTypeInjector = new JsonDataTypeInjector(
			new SerializationModifier(),
			new CallbackFactory(),
			$dataTypeLookup,
			$entityIdParser
		);
		$this->rdfWriterFactory = new RdfWriterFactory();
	}

	/**
	 * Output entity data.
	 *
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param EntityRevision $entityRevision The entity
	 * @param RedirectRevision|null $followedRedirect The redirect that led to the entity, or null
	 * @param EntityId[] $incomingRedirects Incoming redirects to include in the output
	 * @param string|null $flavor The type of the output provided by serializer
	 *
	 * @return array tuple of ( $data, $contentType )
	 * @throws UnknownFlavorException
	 */
	public function getSerializedData(
		string $format,
		EntityRevision $entityRevision,
		?RedirectRevision $followedRedirect = null,
		array $incomingRedirects = [],
		?string $flavor = null
	): array {

		$formatName = $this->entityDataFormatProvider->getFormatName( $format );

		if ( $formatName === null ) {
			throw new InvalidArgumentException( "Unsupported format: $format" );
		}

		if ( $formatName === 'json' ) {
			/** In the interests of avoiding the Api result formatters (T98035) we need to
			 * reproduce the subset of the functionality of ResultBuilder that SpecialEntityData
			 * uses. @see \Wikibase\Repo\Api\ResultBuilder::getModifiedEntityArray
			 */
			$serializedEntity = [];
			$pageInfoAdder = new AddPageInfo( $this->entityTitleStoreLookup );
			$serializedEntity = $pageInfoAdder->add( $serializedEntity, $entityRevision );
			$serializedEntity = array_merge(
				$serializedEntity,
				$this->serializer->serialize( $entityRevision->getEntity() )
			);
			$serializedEntity = $this->dataTypeInjector->injectEntitySerializationWithDataTypes( $serializedEntity );
			$data = FormatJson::encode( [
				'entities' => [
					$entityRevision->getEntity()->getId()->getSerialization() => $serializedEntity,
				],
			], false, FormatJson::XMLMETA_OK );
			$contentType = 'application/json';
		} else {
			$rdfBuilder = $this->createRdfBuilder( $formatName, $flavor );

			if ( $rdfBuilder === null ) {
				throw new RuntimeException( "Could not create serializer for $formatName" );
			}

			$data = $this->rdfSerialize( $entityRevision, $followedRedirect, $incomingRedirects, $rdfBuilder, $flavor );

			$mimeTypes = $this->rdfWriterFactory->getMimeTypes( $formatName );
			$contentType = reset( $mimeTypes );
		}

		return [ $data, $contentType ];
	}

	/**
	 * @param EntityRevision $entityRevision
	 * @param RedirectRevision|null $followedRedirect a redirect leading to the entity for use in the output
	 * @param EntityId[] $incomingRedirects Incoming redirects to include in the output
	 * @param RdfBuilder $rdfBuilder
	 * @param string|null $flavor The type of the output provided by serializer
	 *
	 * @return string RDF
	 */
	private function rdfSerialize(
		EntityRevision $entityRevision,
		?RedirectRevision $followedRedirect,
		array $incomingRedirects,
		RdfBuilder $rdfBuilder,
		?string $flavor = null
	): string {
		$rdfBuilder->startDocument();
		$redir = null;

		if ( $followedRedirect ) {
			$redir = $followedRedirect->getRedirect();
			$rdfBuilder->addEntityRedirect( $redir->getEntityId(), $redir->getTargetId() );

			if ( $followedRedirect->getRevisionId() > 0 ) {
				$rdfBuilder->addEntityRevisionInfo(
					$redir->getEntityId(),
					$followedRedirect->getRevisionId(),
					$followedRedirect->getTimestamp()
				);
			}
		}

		if ( $followedRedirect && $flavor === 'dump' ) {
			// For redirects, don't output the target entity data if the "dump" flavor is requested.
			// @todo: In this case, avoid loading the Entity all together.
			// However we want to output the revisions for redirects
		} else {
			$rdfBuilder->addEntityRevisionInfo(
				$entityRevision->getEntity()->getId(),
				$entityRevision->getRevisionId(),
				$entityRevision->getTimestamp()
			);

			$rdfBuilder->addEntityPageProps( $entityRevision->getEntity() );

			$rdfBuilder->addEntity( $entityRevision->getEntity() );
			$rdfBuilder->resolveMentionedEntities();
		}

		if ( $flavor !== 'dump' ) {
			// For $flavor === 'dump' we don't need to output incoming redirects.

			$targetId = $entityRevision->getEntity()->getId();
			$this->addIncomingRedirects( $targetId, $redir, $incomingRedirects, $rdfBuilder );
		}

		$rdfBuilder->finishDocument();

		return $rdfBuilder->getRDF();
	}

	/**
	 * @param EntityId $targetId
	 * @param EntityRedirect|null $followedRedirect The followed redirect, will be omitted from the
	 * output.
	 * @param EntityId[] $incomingRedirects
	 * @param RdfBuilder $rdfBuilder
	 */
	private function addIncomingRedirects(
		EntityId $targetId,
		?EntityRedirect $followedRedirect,
		array $incomingRedirects,
		RdfBuilder $rdfBuilder
	): void {
		foreach ( $incomingRedirects as $rId ) {
			// don't add the followed redirect again
			if ( !$followedRedirect || !$followedRedirect->getEntityId()->equals( $rId ) ) {
				$rdfBuilder->addEntityRedirect( $rId, $targetId );
			}
		}
	}

	/**
	 * Get the producer setting for current data format
	 *
	 * @throws UnknownFlavorException
	 */
	private function getFlavor( ?string $flavorName ): int {
		switch ( $flavorName ) {
			case 'simple':
				return RdfProducer::PRODUCE_TRUTHY_STATEMENTS
					| RdfProducer::PRODUCE_SITELINKS
					| RdfProducer::PRODUCE_VERSION_INFO;
			case 'dump':
				return RdfProducer::PRODUCE_ALL_STATEMENTS
					| RdfProducer::PRODUCE_TRUTHY_STATEMENTS
					| RdfProducer::PRODUCE_QUALIFIERS
					| RdfProducer::PRODUCE_REFERENCES
					| RdfProducer::PRODUCE_SITELINKS
					| RdfProducer::PRODUCE_FULL_VALUES
					| RdfProducer::PRODUCE_PAGE_PROPS
					| RdfProducer::PRODUCE_NORMALIZED_VALUES
					| RdfProducer::PRODUCE_VERSION_INFO;
			case 'long':
				return RdfProducer::PRODUCE_ALL_STATEMENTS
					| RdfProducer::PRODUCE_QUALIFIERS
					| RdfProducer::PRODUCE_REFERENCES
					| RdfProducer::PRODUCE_SITELINKS
					| RdfProducer::PRODUCE_VERSION_INFO;
			case 'full':
			case null:
				return RdfProducer::PRODUCE_ALL;
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable name is not-null here
		throw new UnknownFlavorException( $flavorName, [ 'simple', 'dump', 'long', 'full' ] );
	}

	/**
	 * Creates an Rdf Serializer that can generate the given output format.
	 *
	 * @param string $format The desired serialization format, as a format name understood by ApiBase or RdfWriterFactory
	 * @param string|null $flavorName Flavor name (used for RDF output)
	 *
	 * @throws UnknownFlavorException
	 * @return RdfBuilder|null A suitable result printer, or null
	 *   if the given format is not supported.
	 */
	private function createRdfBuilder( string $format, ?string $flavorName ): ?RdfBuilder {
		$canonicalFormat = $this->rdfWriterFactory->getFormatName( $format );

		if ( !$canonicalFormat ) {
			return null;
		}

		$rdfWriter = $this->rdfWriterFactory->getWriter( $format );

		return $this->rdfBuilderFactory->getRdfBuilder( $this->getFlavor( $flavorName ), new HashDedupeBag(), $rdfWriter );
	}

}
