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
	 * @var int Cache dureation in seconds
	 */
	protected $maxAge = 0;

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
		$format = 'json';

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

		//TODO: move this, so we can inject alternative values
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$this->rdfBaseURI = $repo->getRdfBaseURI();
		$this->entityLookup = \Wikibase\StoreFactory::getStore()->getEntityLookup();
		$this->dataTypeFactory = $repo->getDataTypeFactory();
		$this->idFormatter = $repo->getIdFormatter();
		$this->maxAge = \Wikibase\Settings::get( 'dataSquidMaxAge' );

		$this->showData( $format, $id, $revision );
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

		$serializer = $this->createApiSerializer( $format );

		if ( !$serializer ) {
			$serializer = $this->createRdfSerializer( $format );
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
	public static function getApiFormatName( $format ) {
		$format = trim( strtolower( $format ) );

		if ( $format === 'application/vnd.php.serialized' ) {
			$format = 'php';
		} else {
			// hack: just trip the major part of the mime type
			$format = preg_replace( '@^(text|application)?/@', '', $format );
		}

		return $format;
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
	protected function createApiSerializer( $format ) {
		//MediaWiki formats
		$api = $this->getApiMain( $format );
		$formats = $api->getFormats();
		$formatName = self::getApiFormatName( $format );
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
		// NOTE: similar code as in RawAction::onView, keep in sync.

		$request = $this->getRequest();
		$response = $request->response();

		$maxage = $request->getInt( 'maxage', $this->maxAge );
		$smaxage = $request->getInt( 'smaxage', $this->maxAge );

		// XXX: do we want public caching even for data from old revisions?
		// Sanity: 0 to 31 days. // todo: Hard maximum could be configurable somehow.
		$maxage  = max( 0, min( 60 * 60 * 24 * 31, $maxage ) );
		$smaxage = max( 0, min( 60 * 60 * 24 * 31, $smaxage ) );

		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );
		$response->header( 'Content-Length: ' . strlen( $data ) );

		if ( $revision ) {
			$response->header( 'Last-Modified: ' . wfTimestamp( TS_RFC2822, $revision->getTimestamp() ) );
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
		ob_clean();

		print $data;
		flush();

		//die(); //FIXME: figure out how to best shut down here.
	}

}
