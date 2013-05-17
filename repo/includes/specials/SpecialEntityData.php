<?php

use DataTypes\DataTypeFactory;
use \Wikibase\Entity;
use \Wikibase\EntityContent;
use \Wikibase\EntityContentFactory;
use \Wikibase\EntityId;
use \Wikibase\RdfSerializer;

/**
 * Special page to act as a data endpoint for the linked data web.
 * The web server should generally be configured to make this accessible via a canonical URL/URI,
 * such as <http://my.domain.org/data/Q12345>.
 *
 * Note that this is implemented as a special page and not a per-page action, so there is no need
 * for the web server to map ID prefixes to wiki namespaces.
 *
 * Also note that we are using the API's serialization facility to ensure a consistent external
 * representation of data entities. Using the ContentHandler to serialize the entity would expose
 * internal implementation details.
 *
 * @since 0.4
 *
 * @file 
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thomas Pellissier Tanon
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class SpecialEntityData extends SpecialWikibasePage {

	const DEFAULT_FORMAT = 'json';

	/**
	 * White list of supported formats.
	 *
	 * @var array
	 */
	static $formatWhiteList = array(
		'json',
		'php',
		'xml',
		'rdfxml',
		'n3',
		'turtle'
	);

	/**
	 * Attributes that should be included in the serialized form of the entity.
	 * That is, all well known attributes.
	 *
	 * @var array
	 */
	public static $fieldsToShow = array(
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
	protected $rdfBaseURI = null;

	/**
	 * @var Wikibase\EntityLookup
	 */
	protected $entityLookup = null;

	/**
	 * @var DataTypeFactory
	 */
	protected $dataTypeFactory = null;

	/**
	 * @var \Wikibase\Lib\EntityIdFormatter
	 */
	protected $idFormatter = null;

	/**
	 * @var null|array Associative array from MIME type to format name
	 * @note: initialized by initFormats()
	 */
	protected $mimeTypes = null;

	/**
	 * @var null|array Associative array from file extension to format name
	 * @note: initialized by initFormats()
	 */
	protected $fileExtensions = null;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'EntityData' );
	}

	/**
	 * Main method.
	 *
	 * @since 0.4
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$revision = 0;
		$format = '';

		// get format from $subPage or request param
		if ( preg_match( '#\.([-./\w]+)$#', $subPage, $m ) ) {
			$subPage = preg_replace( '#\.([-./\w]+)$#', '', $subPage );
			$format = $m[1];
		}

		$format = $this->getRequest()->getText( 'format', $format );

		// get revision from $subPage or request param
		if ( preg_match( '#:(\w+)$#', $subPage, $m ) ) {
			$subPage = preg_replace( '#:(\w+)$#', '', $subPage );
			$revision = (int)$m[1];
		}

		$revision = $this->getRequest()->getInt( 'oldid', $revision );
		$revision = $this->getRequest()->getInt( 'revision', $revision );

		// get entity from remaining $subPage or request param
		$id = $subPage;
		$id = $this->getRequest()->getText( 'id', $id );

		if ( $id === null || $id === '' ) {
			$this->showForm();
			return;
		}

		if ( $format === null || $format === '' ) {
			$headers = $this->getRequest()->getAllHeaders();
			if ( isset( $headers['ACCEPT'] ) ) {
				$negotiator = new \Wikibase\HttpAcceptNegotiator( $this->getSupportedMimeTypes() );

				$parser = new \Wikibase\HttpAcceptParser();
				$accept = $parser->parseWeights( $headers['ACCEPT'] );

				$format = $negotiator->getBestSupportedKey( $accept, self::DEFAULT_FORMAT );

				//TODO: determine canonical title (get extension for MIME type)
				//TODO: trigger 303 redirect

				//XXX: if no match, fail or default?!
			}
		}

		if ( $format === null || $format === '' ) {
			$format = self::DEFAULT_FORMAT;
		}

		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$this->rdfBaseURI = $repo->getRdfBaseURI();
		$this->entityLookup = \Wikibase\StoreFactory::getStore()->getEntityLookup();
		$this->dataTypeFactory = $repo->getDataTypeFactory();
		$this->idFormatter = $repo->getIdFormatter();

		$this->showData( $format, $id, $revision );
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
	 * Returns a canonical format name.
	 *
	 * @param string $format the format as given in the request, as a file extension or MIME type.
	 *
	 * @return string|null the canonical format name, or null of the format is not supported
	 */
	public function getFormatName( $format ) {
		$this->initFormats();

		$format = trim( strtolower( $format ) );

		if ( isset( $this->mimeTypes[$format] ) ) {
			return $this->mimeTypes[$format];
		}

		if ( isset( $this->fileExtensions[$format] ) ) {
			return $this->fileExtensions[$format];
		}

		return null;
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

		$api = $this->getApiMain( "dummy" );
		$formats = $api->getFormats();

		foreach ( $formats as $name => $class ) {
			if ( !in_array( $name, self::$formatWhiteList ) ) {
				continue;
			}

			$mime = self::getApiMimeType( $name );
			$ext = self::getApiFormatName( $name );

			$this->mimeTypes[ $mime ] = $name;
			$this->fileExtensions[ $ext ] = $name;
		}

		if ( \Wikibase\RdfSerializer::isSupported() ) {
			$formats = EasyRdf_Format::getFormats();

			/* @var EasyRdf_Format $format */
			foreach ( $formats as $format ) {
				$name = $format->getName();

				// check whitelist, and don't override API formats
				if ( !in_array( $name, self::$formatWhiteList )
					|| in_array( $name, $this->mimeTypes )
					|| in_array( $name, $this->fileExtensions )) {
					continue;
				}

				// use all mime types. to improve content negotiation
				foreach ( array_keys( $format->getMimeTypes() ) as $mime ) {
					$this->mimeTypes[ $mime ] = $name;
				}

				// use only one file extension, to keep purging simple
				if ( $format->getDefaultExtension() ) {
					$ext = $format->getDefaultExtension();
					$this->fileExtensions[ $ext ] = $name;
				}
			}
		}
	}

	/**
	 * Output entity data.
	 *
	 * @param string $format The name (mime type of file extension) of the format to use
	 * @param string $id The entity ID
	 * @param int|string $revision The entity revision
	 *
	 * @throws HttpError
	 */
	public function showData( $format, $id, $revision ) {

		//TODO: handle IfModifiedSince!

		$serializer = null;
		$formatName = $this->getFormatName( $format );

		if ( $formatName !== null ) {
			$serializer = $this->createApiSerializer( $formatName );

			if ( !$serializer ) {
				$serializer = $this->createRdfSerializer( $formatName );
			}

			if ( !$serializer ) {
				wfWarn( "Could not create serializer using name `$formatName`, even though it is registered." );
			}
		}

		if ( !$serializer ) {
			throw new \HttpError( 415, wfMessage( 'wikibase-entitydata-unsupported-format' )->params( $format ) );
		}

		$eid = EntityId::newFromPrefixedId( strtolower( $id ) ); //XXX: newFromPrefixedId should accept upper-case prefix
		$entity = $eid ? EntityContentFactory::singleton()->getFromId( $eid ) : null;

		if ( !$entity ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": entity not found: $id"  );
			throw new \HttpError( 404, wfMessage( 'wikibase-entitydata-not-found' )->params( $id ) );
		}

		$page = $entity->getWikiPage();

		if ( $revision > 0 ) {
			// get the desired revision
			$rev = Revision::newFromId( $revision );

			if ( $rev === null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision not found: $revision"  );
				$entity = null;
			} elseif ( $rev->getPage() !== $page->getId() ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision $revision does not belong to page "
					. $page->getTitle()->getPrefixedDBkey() );
				$entity = null;
			} elseif ( !EntityContentFactory::singleton()->isEntityContentModel( $rev->getContentModel() ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": revision has bad model: "
					. $rev->getContentModel() );
				$entity = null;
			} else {
				$entity = $rev->getContent();
			}

			if ( !$entity ) {
				//TODO: more specific error message
				$msg = wfMessage( 'wikibase-entitydata-bad-revision' );
				throw new \HttpError( 404, $msg->params( $eid->getPrefixedId(), $revision ) );
			}
		} else {
			$rev = $page->getRevision();
		}


		if( $serializer instanceof \Wikibase\RdfSerializer ) {
			$data = $serializer->serializeEntity( $entity->getEntity(), $rev );
			$contentType = $serializer->getDefaultMimeType();
		} else {
			$data = $this->apiSerialize( $entity->getEntity(), $serializer, $rev );
			$contentType = $serializer->getIsHtml() ? 'text/html' : $serializer->getMimeType();
		}

		$this->outputData( $data, $contentType, $rev );
	}

	/**
	 * Shows an informative page to the user; Called when there is no entity to output.
	 */
	public function showForm() {
		//TODO: show input form with selector for format and field for ID. Add some explanation,
		//      point to meta-info like schema and license, and generally be a helpful data endpoint.
		$this->getOutput()->showErrorPage( 'wikibase-entitydata-title', 'wikibase-entitydata-text' );
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
	 * Calls to this method always return the same singleton object. That singleton
	 * also acts as a holder for the ApiResult used to represent the output before serialization.
	 *
	 * @param String $format The desired output format, as a format name that ApiBase understands.
	 *
	 * @return ApiMain
	 */
	protected function getApiMain( $format ) {
		static $api = null;

		if ( !$api ) {

			// Fake request params to ApiMain, with forced format parameters.
			// We can override additional parameters here, as needed.
			$params = array(
				'format' => $format,
			);

			$context = new DerivativeContext( $this->getContext() );
			$req = new DerivativeRequest( $context->getRequest(), $params );
			$context->setRequest( $req );

			$api = new ApiMain( $context );
		}

		return $api;
	}

	/**
	 * Creates an API printer that can generate the given output format.
	 *
	 * @param String $format The desired serialization format,
	 *   as a format name understood by ApiBase or EasyRdf_Format
	 *
	 * @return ApiFormatBase|null A suitable result printer, or null
	 *         if the given format is not supported by the API.
	 */
	protected function createApiSerializer( $formatName ) {
		//MediaWiki formats
		$api = $this->getApiMain( $formatName );
		$formats = $api->getFormats();
		if ( $formatName !== null && array_key_exists( $formatName, $formats ) ) {
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
	protected function createRdfSerializer( $format ) {
		//MediaWiki formats
		$rdfFormat = \Wikibase\RdfSerializer::getFormat( $format );

		if ( !$rdfFormat ) {
			return null;
		}

		$serializer = new RdfSerializer(
			$rdfFormat,
			$this->rdfBaseURI,
			$this->entityLookup,
			$this->dataTypeFactory,
			$this->idFormatter
		);

		return $serializer;
	}

	/**
	 * Pushes the given $entity into the ApiResult held by the ApiMain module
	 * returned by getApiMain(). Calling $printer->execute() later will output this
	 * result, if $printer was generated from that same ApiMain module, as
	 * createApiPrinter() does.
	 *
	 * @param Wikibase\Entity $entity The entity to convert ot an ApiResult
	 * @param ApiFormatBase $printer The output printer that will be used for serialization.
	 *   Used to provide context for generating the ApiResult, and may also be manipulated
	 *   to fine-tune the output.
	 *
	 * @return ApiResult
	 */
	protected function generateApiResult( Entity $entity, ApiFormatBase $printer ) {
		wfProfileIn( __METHOD__ );

		$format = strtolower( $printer->getFormat() ); //XXX: hack!

		$entityKey = 'entity'; //XXX: perhaps better: $entity->getType();
		$basePath = array();

		$res = $this->getApiMain( $format )->getResult();

		// Make sure result is empty. May still be full if this
		// function gets called multiple times during testing, etc.
		$res->reset();

		if ( $printer->getNeedsRawData() ) {
			$res->setRawMode();
		}

		if ( $printer instanceof ApiFormatXml ) {
			// XXX: hack to force the top level element's name
			$printer->setRootElement( $entityKey );
		}

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer =$serializerFactory->newSerializerForObject( $entity );

		$opt = new \Wikibase\Lib\Serializers\EntitySerializationOptions();
		$opt->setIndexTags( $res->getIsRawMode() ); //FIXME: $res->rawMode doesn't seem to be set to what we want.
		$opt->setProps( self::$fieldsToShow );      //FIXME: someone does not know how to write clear FIXMEs
		$serializer->setOptions( $opt );

		$arr = $serializer->getSerialized( $entity );

		// we want the entity to *be* the result, not *in* the result
		foreach ( $arr as $key => $value ) {
			$res->addValue( $basePath, $key, $value );
		}

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
	 * @param Wikibase\Entity $entity the entity to output.
	 * @param ApiFormatBase $printer the printer to use to generate the output
	 * @param Revision $revision the entity's revision (optional)
	 *
	 * @return string the serialized data
	 */
	public function apiSerialize( Entity $entity, ApiFormatBase $printer, Revision $revision = null ) {
		// NOTE: The way the ApiResult is provided to $printer is somewhat
		//       counter-intuitive. Basically, the relevant ApiResult object
		//       is owned by the ApiMain module provided by getApiMain().

		// Pushes $entity into the ApiResult held by the ApiMain module
		$res = $this->generateApiResult( $entity, $printer );


		//XXX: really inject meta-info? where else should we put it?
		$basePath = array();

		if ( $revision ) {
			$res->addValue( $basePath , '_revision_', intval( $revision->getId() ) );
			$res->addValue( $basePath , '_modified_', wfTimestamp( TS_ISO_8601, $revision->getTimestamp() ) );
		}

		$this->getOutput()->disable(); // don't generate HTML

		$printer->profileIn();
		$printer->initPrinter( false );
		$printer->setBufferResult( true );

		// Outputs the ApiResult held by the ApiMain module, which is hopefully the same as $res
		//NOTE: this can and will mess with the HTTP response!
		$printer->execute();
		$data = $printer->getBuffer();

		$printer->closePrinter();
		$printer->profileOut();

		return $data;
	}

	/**
	 * Output the entity data and set the appropriate HTTP response headers.
	 *
	 * @param string $data the data to output
	 * @param string $contentType the data's mime type
	 * @param Revision $revision
	 */
	public function outputData( $data, $contentType, Revision $revision = null ) {
		global $wgSquidMaxage;

		// NOTE: similar code as in RawAction::onView, keep in sync.

		$request = $this->getRequest();
		$response = $request->response();

		$maxage = $request->getInt( 'maxage', $wgSquidMaxage );
		$smaxage = $request->getInt( 'smaxage', $wgSquidMaxage );

		// Sanity: 0 to 30 days. // todo: Hard maximum could be configurable somehow.
		$maxage  = max( 0, min( 60 * 60 * 24 * 30, $maxage ) );
		$smaxage = max( 0, min( 60 * 60 * 24 * 30, $smaxage ) );

		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );
		$response->header( 'Content-Length: ' . strlen( $data ) );

		if ( $revision ) {
			$response->header( 'Last-Modified: ' . wfTimestamp( TS_ISO_8601, $revision->getTimestamp() ) );
		}

		//Set X-Frame-Options API results (bug 39180)
		global $wgApiFrameOptions;
		if ( $wgApiFrameOptions ) {
			$response->header( "X-Frame-Options: $wgApiFrameOptions" );
		}

		// allow the client to cache this
		$mode = 'public';
		$response->header( 'Cache-Control: ' . $mode . ', s-maxage=' . $smaxage . ', max-age=' . $maxage );

		$this->getOutput()->disable(); // don't generate HTML
		ob_clean(); // remove anything that might already be in the output buffer.

		print $data;

		// exit normally here, keeping all levels of output buffering.
	}

	/**
	 * Returns true iff RDF output is supported.
	 * @return bool
	 */
	public function isRdfSupported() {
		return RdfSerializer::isSupported();
	}
}
