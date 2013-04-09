<?php

use \Wikibase\EntityContent;
use \Wikibase\EntityContentFactory;
use \Wikibase\EntityId;

//XXX: our special pages are not in the wikibase namespace. why? some crappy internal magic going on?

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
 * @since 0.3
 *
 * @file 
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
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

	protected static $rdfAllowedTypes = array(
		'rdfxml',
		'ntriples'
	);

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'EntityData' );
	}

	/**
	 * Main method.
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
	 *
	 * @return boolean
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

		//TODO: handle IfModifiedSince!


		$printer = $this->createApiPrinter( $format );

		if ( !$printer ) {
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

		$this->showData( $entity, $printer, $rev );
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
	 * Creates a printer that can generate the given output format.
	 *
	 * @param String $format The desired serialization format,
	 *   as a format name understood by ApiBase or EasyRdf_Format
	 *
	 * @return ApiFormatBase|EasyRdf_Format|null A suitable result printer, or null
	 *   if the given format is not supported.
	 */
	protected function createApiPrinter( $format ) {
		//MediaWiki formats
		$api = $this->getApiMain( $format );
		$formats = $api->getFormats();
		$formatName = self::getApiFormatName( $format );
		if ( $formatName !== null && array_key_exists( $formatName, $formats ) ) {
			return $api->createPrinterByName( $formatName );;
		}

		//EasyRdf formats
		try {
			$rdfFormat = EasyRdf_Format::getFormat( $format );
			if ( !in_array( $rdfFormat->getName(), self::$rdfAllowedTypes ) ) {
				return null;
			}
			return $rdfFormat;
		} catch ( EasyRdf_Exception $e ) { //Unknown format
			return null;
		}
	}

	/**
	 * Pushes the given $entity into the ApiResult held by the ApiMain module
	 * returned by getApiMain(). Calling $printer->execute() later will output this
	 * result, if $printer was generated from that same ApiMain module, as
	 * createApiPrinter() does.
	 *
	 * @param Wikibase\EntityContent $entityContent The entity to convert ot an ApiResult
	 * @param ApiFormatBase $printer The output printer that will be used for serialization.
	 *   Used to provide context for generating the ApiResult, and may also be manipulated
	 *   to fine-tune the output.
	 *
	 * @return ApiResult
	 */
	protected function generateApiResult( EntityContent $entityContent, ApiFormatBase $printer ) {
		wfProfileIn( __METHOD__ );

		$format = strtolower( $printer->getFormat() ); //XXX: hack!

		$entityKey = 'entity'; //XXX: perhaps better: $entity->getEntity()->getType();
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
		$serializer =$serializerFactory->newSerializerForObject( $entityContent->getEntity() );

		$opt = new \Wikibase\Lib\Serializers\EntitySerializationOptions();
		$opt->setIndexTags( $res->getIsRawMode() ); //FIXME: $res->rawMode doesn't seem to be set to what we want.
		$opt->setProps( self::$fieldsToShow );      //FIXME: someone does not know how to write clear FIXMEs
		$serializer->setOptions( $opt );

		$arr = $serializer->getSerialized( $entityContent->getEntity() );

		// we want the entity to *be* the result, not *in* the result
		foreach ( $arr as $key => $value ) {
			$res->addValue( $basePath, $key, $value );
		}

		wfProfileOut( __METHOD__ );
		return $res;
	}

	/**
	 * Serialize the entity data using the provided format and send it as the HTTP response body.
	 *
	 * Note that we are using the API's serialization facility to ensure a consistent external
	 * representation of data entities. Using the ContentHandler to serialize the entity would
	 * expose internal implementation details.
	 *
	 * @param Wikibase\EntityContent $entity the entity to output.
	 * @param ApiFormatBase $printer the printer to use to generate the output
	 * @param Revision $revision the entity's revision (optional)
	 */
	public function output( EntityContent $entity, ApiFormatBase $printer, Revision $revision ) {
		// NOTE: The way the ApiResult is provided to $printer is somewhat
		//       counter-intuitive. Basically, the relevant ApiResult object
		//       is owned by the ApiMain module provided by getApiMain().

		// Pushes $entity into the ApiResult held by the ApiMain module
		$res = $this->generateApiResult( $entity, $printer );


		//XXX: really inject meta-info? where else should we put it?
		$basePath = array();
		$res->addValue( $basePath , '_revision_', intval( $revision->getId() ) );
		$res->addValue( $basePath , '_modified_', wfTimestamp( TS_ISO_8601, $revision->getTimestamp() ) );


		$this->getOutput()->disable(); // don't generate HTML

		$printer->profileIn();
		$printer->initPrinter( false );

		// Outputs the ApiResult held by the ApiMain module, which is hopefully the same as $res
		$printer->execute();

		$printer->closePrinter();
		$printer->profileOut();

		//FIXME: Content-Length is bogus! Where does it come from?!
	}


	/**
	 * Serialize the entity data in RDF using the provided format and send it as the HTTP response body.
	 *
	 * @param Wikibase\EntityContent $entity the entity to output.
	 * @param ApiFormatBase $format the format to use to generate the output
	 * @param Revision $revision the entity's revision (optional)
	 */
	public function outputRdf( EntityContent $entity, EasyRdf_Format $format, Revision $revision ) {
		$this->getOutput()->disable(); // don't generate HTML

		$linkedDataSerializer = new \Wikibase\LinkedDataSerializer();
		$linkedDataSerializer->addEntity( $entity );
		echo $linkedDataSerializer->outputRdf( $format );
	}

	/**
	 * Output the entity data and set the appropriate HTTP response headers.
	 *
	 * @param Wikibase\EntityContent $entity the entity to output.
	 * @param ApiFormatBase|EasyRdf_Format $printer the printer to use to generate the output
	 * @param Revision $revision the entity's revision (optional)
	 *
	 * @throws HttpError If an unsupported format is requested.
	 */
	public function showData( EntityContent $entity, $printer, Revision $revision = null ) {
		global $wgSquidMaxage;

		// NOTE: similar code as in RawAction::onView, keep in sync.

		$request = $this->getRequest();
		$response = $request->response();

		$maxage = $request->getInt( 'maxage', $wgSquidMaxage );
		$smaxage = $request->getInt( 'smaxage', $wgSquidMaxage );

		// Sanity: 0 to 30 days. // todo: Hard maximum could be configurable somehow.
		$maxage  = max( 0, min( 60 * 60 * 24 * 30, $maxage ) );
		$smaxage = max( 0, min( 60 * 60 * 24 * 30, $smaxage ) );

		//TODO: set Last-Modified header? Why doesn't mediawiki set that for article pages?

		// make sure we are reporting the correct content type
		if( $printer instanceof EasyRdf_Format ) {
			$contentType = $printer->getDefaultMimeType();
		} else {
			$contentType = $printer->getIsHtml() ? 'text/html' : $printer->getMimeType();
		}
		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );

		// allow the client to cache this
		$mode = 'public';
		$response->header( 'Cache-Control: ' . $mode . ', s-maxage=' . $smaxage . ', max-age=' . $maxage );
		//FIXME: Cache-Control and Expires headers are apparently overwritten later!

		if( $printer instanceof EasyRdf_Format ) {
			$this->outputRdf( $entity, $printer, $revision );
		} else {
			$this->output( $entity, $printer, $revision );
		}
	}

}
