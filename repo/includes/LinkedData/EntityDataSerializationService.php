<?php

namespace Wikibase\Repo\LinkedData;

use ApiFormatBase;
use ApiFormatXml;
use ApiMain;
use ApiResult;
use DerivativeContext;
use DerivativeRequest;
use EasyRdf_Format;
use MWException;
use RequestContext;
use SiteList;
use Wikibase\Api\ResultBuilder;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\RdfSerializer;

/**
 * Service for serializing entity data.
 *
 * Note that we are using the API's serialization facility to ensure a consistent external
 * representation of data entities. Using the ContentHandler to serialize the entity would expose
 * internal implementation details.
 *
 * For RDF output, this relies on the RdfSerializer class.
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
	 * @var array
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
	 * @var string
	 */
	private $rdfBaseURI = null;

	/**
	 * @var string
	 */
	private $rdfDataURI = null;

	/**
	 * @var EntityLookup
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
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @param string $rdfBaseURI
	 * @param string $rdfDataURI
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializerFactory $serializerFactory
	 * @param SiteList $sites
	 *
	 * @since 0.4
	 */
	public function __construct(
		$rdfBaseURI,
		$rdfDataURI,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup,
		SerializerFactory $serializerFactory,
		SiteList $sites
	) {
		$this->rdfBaseURI = $rdfBaseURI;
		$this->rdfDataURI = $rdfDataURI;
		$this->entityLookup = $entityLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->serializerFactory = $serializerFactory;
		$this->sites = $sites;
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

		// force re-init of format maps
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
	protected function initFormats() {
		if ( $this->mimeTypes !== null
			&& $this->fileExtensions !== null ) {
			return;
		}

		$this->mimeTypes = array();
		$this->fileExtensions = array();

		$api = $this->newApiMain( "dummy" );
		$formatNames = $api->getModuleManager()->getNames( 'format' );

		foreach ( $formatNames as $name ) {
			if ( $this->formatWhiteList !== null && !in_array( $name, $this->formatWhiteList ) ) {
				continue;
			}

			$mime = self::getApiMimeType( $name );
			$ext = self::getApiFormatName( $name );

			$this->mimeTypes[ $mime ] = $name;
			$this->fileExtensions[ $ext ] = $name;
		}

		$formats = EasyRdf_Format::getFormats();

		/* @var EasyRdf_Format $format */
		foreach ( $formats as $format ) {
			$name = $format->getName();

			// check whitelist, and don't override API formats
			if ( ( $this->formatWhiteList !== null
					&& !in_array( $name, $this->formatWhiteList ) )
				|| in_array( $name, $this->mimeTypes )
				|| in_array( $name, $this->fileExtensions )) {
				continue;
			}

			// use all mime types. to improve content negotiation
			foreach ( array_keys( $format->getMimeTypes() ) as $mime ) {
				$this->mimeTypes[ $mime ] = $name;
			}

			// use only one file extension, to keep purging simple
			if ( $format->getExtensions() && $format->getDefaultExtension() ) {
				$ext = $format->getDefaultExtension();
				$this->fileExtensions[ $ext ] = $name;
			}
		}
	}

	/**
	 * Output entity data.
	 *
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param EntityRevision $entityRevision The entity
	 *
	 * @return array tuple of ( $data, $contentType )
	 * @throws MWException if the format is not supported
	 */
	public function getSerializedData( $format, EntityRevision $entityRevision ) {

		//TODO: handle IfModifiedSince!

		$formatName = $this->getFormatName( $format );

		if ( $formatName === null ) {
			throw new MWException( "Unsupported format: $format" );
		}

		$serializer = $this->createApiSerializer( $formatName );

		if ( !$serializer ) {
			$serializer = $this->createRdfSerializer( $formatName );
		}

		if ( !$serializer ) {
			throw new MWException( "Could not create serializer for $formatName" );
		}

		if( $serializer instanceof ApiFormatBase ) {
			$data = $this->apiSerialize( $entityRevision, $serializer );
			$contentType = $serializer->getIsHtml() ? 'text/html' : $serializer->getMimeType();
		} else {
			$data = $serializer->serializeEntityRevision( $entityRevision );
			$contentType = $serializer->getDefaultMimeType();
		}

		return array( $data, $contentType );
	}

	/**
	 * Normalizes the format specifier; Converts mime types to API format names.
	 *
	 * @param String $format the format as supplied in the request
	 *
	 * @return String|null the normalized format name, or null if the format is unknown
	 */
	protected static function getApiFormatName( $format ) {
		$format = trim( strtolower( $format ) );

		if ( $format === 'application/vnd.php.serialized' ) {
			$format = 'php';
		} elseif ( $format === 'text/text' || $format === 'text/plain' ) {
			$format = 'txt';
		} else {
			// hack: just trip the major part of the mime type
			$format = preg_replace( '@^(text|application)?/@', '', $format );
		}

		return $format;
	}

	/**
	 * Converts API format names to MIME types.
	 *
	 * @param String $format the API format name
	 *
	 * @return String|null the MIME type for the given format
	 */
	protected static function getApiMimeType( $format ) {
		$format = trim( strtolower( $format ) );
		$type = null;

		if ( $format === 'php' ) {
			$type = 'application/vnd.php.serialized';
		} else if ( $format === 'txt' ) {
			$type = "text/text"; // NOTE: not text/plain, to avoid HTML sniffing in IE7
		} else if ( in_array( $format, array( 'xml', 'javascript', 'text' ) ) ) {
			$type = "text/$format";
		} else {
			// hack: assume application type
			$type = "application/$format";
		}

		return $type;
	}

	/**
	 * Returns an ApiMain module that acts as a context for the formatting and serialization.
	 *
	 * @param String $format The desired output format, as a format name that ApiBase understands.
	 *
	 * @return ApiMain
	 */
	protected function newApiMain( $format ) {
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
	 *           as a format name understood by ApiBase or EasyRdf_Format
	 *
	 * @return \ApiFormatBase|null A suitable result printer, or null
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
	 * Creates an Rdf Serializer that can generate the given output format.
	 *
	 * @param String $format The desired serialization format,
	 *   as a format name understood by ApiBase or EasyRdf_Format
	 *
	 * @return RdfSerializer|null A suitable result printer, or null
	 *   if the given format is not supported.
	 */
	public function createRdfSerializer( $format ) {
		//MediaWiki formats
		$rdfFormat = RdfSerializer::getFormat( $format );

		if ( !$rdfFormat ) {
			return null;
		}

		$serializer = new RdfSerializer(
			$rdfFormat,
			$this->rdfBaseURI,
			$this->rdfDataURI,
			$this->sites,
			$this->entityLookup
		);

		return $serializer;
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
		wfProfileIn( __METHOD__ );

		$entityKey = 'entity'; //XXX: perhaps better: $entity->getType();

		$res = $printer->getResult();

		// Make sure result is empty. May still be full if this
		// function gets called multiple times during testing, etc.
		$res->reset();

		if ( $printer->getNeedsRawData() ) {
			// force raw mode, to trigger indexed tag names
			$res->setRawMode();
		}

		//TODO: apply language filter/Fallback via options!
		$options = new SerializationOptions();

		if ( $printer instanceof ApiFormatXml ) {
			$options->setIndexTags( true );
			// XXX: hack to force the top level element's name
			$printer->setRootElement( $entityKey );
		}

		$resultBuilder = new ResultBuilder(
			$res,
			$this->entityTitleLookup,
			$this->serializerFactory
		);
		$resultBuilder->addEntityRevision( null, $entityRevision, $options );

		wfProfileOut( __METHOD__ );
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
