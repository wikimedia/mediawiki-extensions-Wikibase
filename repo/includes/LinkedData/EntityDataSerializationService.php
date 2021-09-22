<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\LinkedData;

use ApiFormatBase;
use ApiMain;
use ApiResult;
use DerivativeContext;
use DerivativeRequest;
use MWException;
use RequestContext;
use Serializers\Serializer;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Repo\Api\ResultBuilder;
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

	/** @var EntityTitleStoreLookup */
	private $entityTitleStoreLookup;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var RdfWriterFactory
	 */
	private $rdfWriterFactory;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var RdfBuilderFactory
	 */
	private $rdfBuilderFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct(
		EntityTitleStoreLookup $entityTitleStoreLookup,
		PropertyDataTypeLookup $propertyLookup,
		EntityDataFormatProvider $entityDataFormatProvider,
		SerializerFactory $serializerFactory,
		Serializer $entitySerializer,
		SiteLookup $siteLookup,
		RdfBuilderFactory $rdfBuilderFactory,
		EntityIdParser $entityIdParser
	) {
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;
		$this->propertyLookup = $propertyLookup;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->serializerFactory = $serializerFactory;
		$this->entitySerializer = $entitySerializer;
		$this->siteLookup = $siteLookup;
		$this->rdfBuilderFactory = $rdfBuilderFactory;
		$this->entityIdParser = $entityIdParser;

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
	 * @throws MWException
	 */
	public function getSerializedData(
		string $format,
		EntityRevision $entityRevision,
		RedirectRevision $followedRedirect = null,
		array $incomingRedirects = [],
		?string $flavor = null
	): array {

		$formatName = $this->entityDataFormatProvider->getFormatName( $format );

		if ( $formatName === null ) {
			throw new MWException( "Unsupported format: $format" );
		}

		$serializer = $this->createApiSerializer( $formatName );

		if ( $serializer !== null ) {
			$data = $this->getApiSerialization( $entityRevision, $serializer );
			$contentType = $serializer->getIsHtml() ? 'text/html' : $serializer->getMimeType();
		} else {
			$rdfBuilder = $this->createRdfBuilder( $formatName, $flavor );

			if ( $rdfBuilder === null ) {
				throw new MWException( "Could not create serializer for $formatName" );
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
	 * Returns an ApiMain module that acts as a context for the formatting and serialization.
	 *
	 * @param string $format The desired output format, as a format name that ApiBase understands.
	 */
	private function newApiMain( string $format ): ApiMain {
		// Fake request params to ApiMain, with forced format parameters.
		// We can override additional parameters here, as needed.
		$params = [
			'format' => $format,
		];

		$context = new DerivativeContext( RequestContext::getMain() ); //XXX: ugly

		$req = new DerivativeRequest( $context->getRequest(), $params );
		$context->setRequest( $req );

		$api = new ApiMain( $context );
		return $api;
	}

	/**
	 * Creates an API printer that can generate the given output format.
	 *
	 * @param string $formatName The desired serialization format,
	 *           as a format name understood by ApiBase or RdfWriterFactory.
	 *
	 * @return ApiFormatBase|null A suitable result printer, or null
	 *           if the given format is not supported by the API.
	 */
	private function createApiSerializer( string $formatName ): ?ApiFormatBase {
		//MediaWiki formats
		$api = $this->newApiMain( $formatName );
		$formatNames = $api->getModuleManager()->getNames( 'format' );
		if ( in_array( $formatName, $formatNames ) ) {
			return $api->createPrinterByName( $formatName );
		}

		return null;
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

	/**
	 * Pushes the given $entity into the ApiResult held by the ApiMain module
	 * returned by newApiMain(). Calling $printer->execute() later will output this
	 * result, if $printer was generated from that same ApiMain module, as
	 * createApiPrinter() does.
	 *
	 * @param EntityRevision $entityRevision The entity to convert ot an ApiResult
	 * @param ApiFormatBase $printer The output printer that will be used for serialization.
	 *   Used to provide context for generating the ApiResult, and may also be manipulated
	 *   to fine-tune the output.
	 */
	private function generateApiResult( EntityRevision $entityRevision, ApiFormatBase $printer ): ApiResult {
		$res = $printer->getResult();

		// Make sure result is empty. May still be full if this
		// function gets called multiple times during testing, etc.
		$res->reset();

		$resultBuilder = new ResultBuilder(
			$res,
			$this->entityTitleStoreLookup,
			$this->serializerFactory,
			$this->entitySerializer,
			$this->siteLookup,
			$this->propertyLookup,
			$this->entityIdParser,
			true // include metadata for the API result printer
		);
		$resultBuilder->addEntityRevision( null, $entityRevision );

		return $res;
	}

	/**
	 * Serialize the entity data using the provided format.
	 *
	 * Note that we are using the API's serialization facility to ensure a consistent external
	 * representation of data entities. Using the ContentHandler to serialize the entity would
	 * expose internal implementation details.
	 *
	 * @param EntityRevision $entityRevision the entity to output.
	 * @param ApiFormatBase $printer the printer to use to generate the output
	 *
	 * @return string the serialized data
	 */
	private function getApiSerialization(
		EntityRevision $entityRevision,
		ApiFormatBase $printer
	): string {
		// NOTE: The way the ApiResult is provided to $printer is somewhat
		//       counter-intuitive. Basically, the relevant ApiResult object
		//       is owned by the ApiMain module provided by newApiMain().

		// Pushes $entity into the ApiResult held by the ApiMain module
		// TODO: where to put the followed redirect?
		// TODO: where to put the incoming redirects? See T98039
		$this->generateApiResult( $entityRevision, $printer );

		$printer->initPrinter();

		// Outputs the ApiResult held by the ApiMain module, which is hopefully the one we added the entity data to.
		//NOTE: this can and will mess with the HTTP response!
		$printer->execute();
		$data = $printer->getBuffer();

		$printer->disable();

		return $data;
	}

}
