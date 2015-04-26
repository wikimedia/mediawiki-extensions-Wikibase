<?php

namespace Wikibase\Repo\LinkedData;

use ApiFormatBase;
use ApiMain;
use ApiResult;
use DerivativeContext;
use DerivativeRequest;
use MWException;
use RequestContext;
use SiteList;
use Wikibase\Api\ResultBuilder;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriterFactory;
use Wikibase\RdfProducer;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;

/**
 * Service for serializing entity data.
 *
 * Note that we are using the API's serialization facility to ensure a consistent external
 * representation of data entities. Using the ContentHandler to serialize the entity would expose
 * internal implementation details.
 *
 * For RDF output, this relies on the RdfBuilder class.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class EntityDataSerializationService {

	/**
	 * White list of supported formats.
	 *
	 * @var array|null
	 */
	private $formatWhiteList = null;

	/**
	 * Attributes that should be included in the serialized form of the entity.
	 * That is, all well known attributes.
	 *
	 * @var array
	 */
	private $fieldsToShow = array(
		'labels',
		'aliases',
		'descriptions',
		'sitelinks',
		'datatype',
		'claims',
		'statements',
	);

	/**
	 * @var string|null
	 */
	private $rdfBaseURI = null;

	/**
	 * @var string|null
	 */
	private $rdfDataURI = null;

	/**
	 * @var EntityLookup|null
	 */
	private $entityLookup = null;

	/**
	 * @var null|array Associative array from MIME type to format name
	 * @note: initialized by initFormats()
	 */
	private $mimeTypes = null;

	/**
	 * @var null|array Associative array from file extension to format name
	 * @note: initialized by initFormats()
	 */
	private $fileExtensions = null;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @var RdfWriterFactory
	 */
	private $rdfWriterFactory;

	/**
	 * @var EntityDataFormatAccessor
	 */
	private $entityDataFormatAccessor;

	/**
	 * @param string $rdfBaseURI
	 * @param string $rdfDataURI
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializerFactory $serializerFactory
	 * @param SiteList $sites
	 * @param EntityDataFormatAccessor $entityDataFormatAccessor
	 *
	 * @since 0.4
	 */
	public function __construct(
		$rdfBaseURI,
		$rdfDataURI,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup,
		SerializerFactory $serializerFactory,
		PropertyDataTypeLookup $propertyLookup,
		SiteList $sites,
		EntityDataFormatAccessor $entityDataFormatAccessor
	) {
		$this->rdfBaseURI = $rdfBaseURI;
		$this->rdfDataURI = $rdfDataURI;
		$this->entityLookup = $entityLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->serializerFactory = $serializerFactory;
		$this->propertyLookup = $propertyLookup;
		$this->sites = $sites;
		$this->entityDataFormatAccessor = $entityDataFormatAccessor;

		$this->rdfWriterFactory = new RdfWriterFactory();
	}

	/**
	 * @param array $fieldsToShow
	 */
	public function setFieldsToShow( $fieldsToShow ) {
		$this->fieldsToShow = $fieldsToShow;
	}

	/**
	 * @return array
	 */
	public function getFieldsToShow() {
		return $this->fieldsToShow;
	}

	/**
	 * @param array $formatWhiteList
	 */
	public function setFormatWhiteList( $formatWhiteList ) {
		$this->formatWhiteList = $formatWhiteList;

		// Force re-init of format maps
		$this->fileExtensions = null;
		$this->mimeTypes = null;
	}

	/**
	 * @return array
	 */
	public function getFormatWhiteList() {
		return $this->formatWhiteList;
	}

	/**
	 * @param string $rdfBaseURI
	 */
	public function setRdfBaseURI( $rdfBaseURI ) {
		$this->rdfBaseURI = $rdfBaseURI;
	}

	/**
	 * @return string
	 */
	public function getRdfBaseURI() {
		return $this->rdfBaseURI;
	}

	/**
	 * @param string $rdfDataURI
	 */
	public function setRdfDataURI( $rdfDataURI ) {
		$this->rdfDataURI = $rdfDataURI;
	}

	/**
	 * @return string
	 */
	public function getRdfDataURI() {
		return $this->rdfDataURI;
	}

	/**
	 * Returns the list of supported MIME types that can be used to specify the
	 * output format.
	 *
	 * @return string[]
	 */
	public function getSupportedMimeTypes() {
		$this->initFormats();

		return array_keys( $this->mimeTypes );
	}

	/**
	 * Returns the list of supported file extensions that can be used
	 * to specify a format.
	 *
	 * @return string[]
	 */
	public function getSupportedExtensions() {
		$this->initFormats();

		return array_keys( $this->fileExtensions );
	}

	/**
	 * Returns the list of supported formats using their canonical names.
	 *
	 * @return string[]
	 */
	public function getSupportedFormats() {
		$this->initFormats();

		return array_unique( array_merge(
			array_values( $this->mimeTypes ),
			array_values( $this->fileExtensions )
		) );
	}

	/**
	 * Returns a canonical format name. Used to normalize the format identifier.
	 *
	 * @param string $format the format as a file extension or MIME type.
	 *
	 * @return string|null the canonical format name, or null of the format is not supported
	 */
	public function getFormatName( $format ) {
		$this->initFormats();

		$format = trim( strtolower( $format ) );

		if ( array_key_exists( $format, $this->mimeTypes ) ) {
			return $this->mimeTypes[$format];
		}

		if ( array_key_exists( $format, $this->fileExtensions ) ) {
			return $this->fileExtensions[$format];
		}

		if ( in_array( $format, $this->mimeTypes ) ) {
			return $format;
		}

		if ( in_array( $format, $this->fileExtensions ) ) {
			return $format;
		}

		return null;
	}

	/**
	 * Returns a file extension suitable for $format, or null if no such extension is known.
	 *
	 * @param string $format A canonical format name, as returned by getFormatName() or getSupportedFormats().
	 *
	 * @return string|null
	 */
	public function getExtension( $format ) {
		$this->initFormats();

		$ext = array_search( $format, $this->fileExtensions );
		return $ext === false ? null : $ext;
	}

	/**
	 * Returns a MIME type suitable for $format, or null if no such extension is known.
	 *
	 * @param string $format A canonical format name, as returned by getFormatName() or getSupportedFormats().
	 *
	 * @return string|null
	 */
	public function getMimeType( $format ) {
		$this->initFormats();

		$type = array_search( $format, $this->mimeTypes );

		return $type === false ? null : $type;
	}

	/**
	 * Initializes the internal mapping of MIME types and file extensions to format names.
	 */
	private function initFormats() {
		if ( $this->mimeTypes !== null
			&& $this->fileExtensions !== null ) {
			return;
		}

		$this->mimeTypes = $this->entityDataFormatAccessor->getMimeTypes( $this->formatWhiteList );
		$this->fileExtensions = $this->entityDataFormatAccessor->getFileExtensions( $this->formatWhiteList );
	}

	/**
	 * Output entity data.
	 *
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param EntityRevision $entityRevision The entity
	 * @param string|null $flavor The type of the output provided by serializer
	 *
	 * @return array tuple of ( $data, $contentType )
	 * @throws MWException if the format is not supported
	 */
	public function getSerializedData( $format, EntityRevision $entityRevision, $flavor = null ) {

		//TODO: handle IfModifiedSince!

		$formatName = $this->getFormatName( $format );

		if ( $formatName === null ) {
			throw new MWException( "Unsupported format: $format" );
		}

		$serializer = $this->createApiSerializer( $formatName );

		if( $serializer ) {
			$data = $this->apiSerialize( $entityRevision, $serializer );
			$contentType = $serializer->getIsHtml() ? 'text/html' : $serializer->getMimeType();
		} else {
			$rdfBuilder = $this->createRdfBuilder( $formatName, $flavor );

			if ( !$rdfBuilder ) {
				throw new MWException( "Could not create serializer for $formatName" );
			} else {
				$data = $this->rdfSerialize( $entityRevision, $rdfBuilder );

				$mimeTypes = $this->rdfWriterFactory->getMimeTypes( $formatName );
				$contentType = reset( $mimeTypes );
			}
		}

		return array( $data, $contentType );
	}

	/**
	 * @param EntityRevision $entityRevision
	 * @param RdfBuilder $rdfBuilder
	 *
	 * @return string RDF
	 */
	private function rdfSerialize( EntityRevision $entityRevision, RdfBuilder $rdfBuilder ) {
		$rdfBuilder->startDocument();
		$rdfBuilder->addDumpHeader();

		$rdfBuilder->addEntityRevisionInfo(
			$entityRevision->getEntity()->getId(),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp()
		);

		$rdfBuilder->addEntity( $entityRevision->getEntity() );

		$rdfBuilder->resolveMentionedEntities( $this->entityLookup );
		$rdfBuilder->finishDocument();

		return $rdfBuilder->getRDF();
	}

	/**
	 * Returns an ApiMain module that acts as a context for the formatting and serialization.
	 *
	 * @param String $format The desired output format, as a format name that ApiBase understands.
	 *
	 * @return ApiMain
	 */
	private function newApiMain( $format ) {
		// Fake request params to ApiMain, with forced format parameters.
		// We can override additional parameters here, as needed.
		$params = array(
			'format' => $format,
		);

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
	public function createApiSerializer( $formatName ) {
		//MediaWiki formats
		$api = $this->newApiMain( $formatName );
		$formatNames = $api->getModuleManager()->getNames( 'format' );
		if ( $formatName !== null && in_array( $formatName, $formatNames ) ) {
			return $api->createPrinterByName( $formatName );
		}

		return null;
	}

	/**
	 * Get the producer setting for current data format
	 *
	 * @param string|null $flavorName
	 *
	 * @return int
	 * @throws MWException
	 */
	private function getFlavor( $flavorName ) {
		switch( $flavorName ) {
			case 'simple':
				return RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_SITELINKS | RdfProducer::PRODUCE_VERSION_INFO;
			case 'full':
				return RdfProducer::PRODUCE_ALL;
			case 'dump':
				return RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES | RdfProducer::PRODUCE_SITELINKS | RdfProducer::PRODUCE_FULL_VALUES;
			case 'long':
				return RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES | RdfProducer::PRODUCE_SITELINKS | RdfProducer::PRODUCE_VERSION_INFO;
			case null: // No flavor given
				return RdfProducer::PRODUCE_SITELINKS;
		}
		throw new MWException( "Unsupported flavor: $flavorName" );
	}

	/**
	 * Creates an Rdf Serializer that can generate the given output format.
	 *
	 * @param string $format The desired serialization format, as a format name understood by ApiBase or RdfWriterFactory
	 * @param string|null $flavorName Flavor name (used for RDF output)
	 *
	 * @return RdfBuilder|null A suitable result printer, or null
	 *   if the given format is not supported.
	 */
	public function createRdfBuilder( $format, $flavorName = null ) {
		$canonicalFormat = $this->rdfWriterFactory->getFormatName( $format );

		if ( !$canonicalFormat ) {
			return null;
		}

		$rdfWriter = $this->rdfWriterFactory->getWriter( $format );

		$rdfBuilder = new RdfBuilder(
			$this->sites,
			new RdfVocabulary( $this->rdfBaseURI, $this->rdfDataURI ),
			$this->propertyLookup,
			$this->getFlavor( $flavorName ),
			$rdfWriter,
			new HashDedupeBag()
		);

		return $rdfBuilder;
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
	 *
	 * @return ApiResult
	 */
	protected function generateApiResult( EntityRevision $entityRevision, ApiFormatBase $printer ) {
		$res = $printer->getResult();

		// Make sure result is empty. May still be full if this
		// function gets called multiple times during testing, etc.
		$res->reset();

		//TODO: apply language filter/Fallback via options!
		$options = new SerializationOptions();

		$resultBuilder = new ResultBuilder(
			$res,
			$this->entityTitleLookup,
			$this->serializerFactory
		);
		$resultBuilder->addEntityRevision( null, $entityRevision, $options );

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
	public function apiSerialize( EntityRevision $entityRevision, ApiFormatBase $printer ) {
		// NOTE: The way the ApiResult is provided to $printer is somewhat
		//       counter-intuitive. Basically, the relevant ApiResult object
		//       is owned by the ApiMain module provided by newApiMain().

		// Pushes $entity into the ApiResult held by the ApiMain module
		$this->generateApiResult( $entityRevision, $printer );

		$printer->profileIn();
		$printer->initPrinter();

		// Outputs the ApiResult held by the ApiMain module, which is hopefully the one we added the entity data to.
		//NOTE: this can and will mess with the HTTP response!
		$printer->execute();
		$data = $printer->getBuffer();

		$printer->disable();
		$printer->profileOut();

		return $data;
	}

}
