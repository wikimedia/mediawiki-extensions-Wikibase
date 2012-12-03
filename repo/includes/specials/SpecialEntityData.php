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
		$id = $subPage;

		if ( $id === null || $id === '' ) {
			$id = $this->getRequest()->getText( 'id', '' );
		}

		// TODO: load specified revision; wait for getTitleFromId to be merged.
		// $revision = $this->getRequest()->getText( 'revid', null );

		if ( $id === null || $id === '' ) {
			$this->showForm();
			return;
		}

		//TODO: handle IfModifiedSince!

		$format = $this->getRequest()->getText( 'format', 'json' );
		$apiFormat = self::getApiFormatName( $format );
		$printer = $apiFormat === null ? null : $this->createApiPrinter( $apiFormat );

		if ( !$printer ) {
			throw new \HttpError( 415, wfMessage( 'wikibase-entitydata-unsupported-format' )->params( $format ) );
		}

		$eid = EntityId::newFromPrefixedId( $id ); //XXX: newFromPrefixedId should accept upper-case prefix
		$entity = $eid ? EntityContentFactory::singleton()->getFromId( $eid ) : null;

		if ( !$entity ) {
			throw new \HttpError( 404, wfMessage( 'wikibase-entitydata-not-found' )->params( $id ) );
		}

		$this->showData( $entity, $printer );
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
	 *   as a format name understood by ApiBase.
	 *
	 * @return ApiFormatBase|null A suitable result printer, or null
	 *   if the given format is not supported.
	 */
	protected function createApiPrinter( $format ) {
		$api = $this->getApiMain( $format );

		$formats = $api->getFormats();
		if ( !array_key_exists( $format, $formats ) ) {
			return null;
		}

		$printer = $api->createPrinterByName( $format );
		return $printer;
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
		$useKeys = $printer->getResult()->getIsRawMode();

		$entityKey = 'entity'; //XXX: perhaps better: $entity->getEntity()->getType();
		$basePath = array();

		// serialize entity
		$res = $this->getApiMain( $format )->getResult();

		if ( !$useKeys ) {
			$res->setRawMode();
		}

		if ( $printer instanceof ApiFormatXml ) {
			// hack to force the top level element's name
			$printer->setRootElement( $entityKey );
		}

		$opt = new \Wikibase\EntitySerializationOptions();
		$opt->setUseKeys( $useKeys );
		$opt->setProps( self::$fieldsToShow );

		$serializer = \Wikibase\EntitySerializer::newForEntity( $entityContent->getEntity(), $opt );

		$arr = $serializer->getSerialized( $entityContent->getEntity() );

		// we want the entity to *be* the result, not *in* the result
		foreach ( $arr as $key => $value ) {
			$res->addValue( $basePath, $key, $value );
		}

		// inject revision info
		$page = $entityContent->getWikiPage();
		$revision = $page->getRevision(); //FIXME: this is WRONG if a specific revision was requested! remember to change this!

		$res->addValue( $basePath , 'revision', intval( $revision->getId() ) );
		$res->addValue( $basePath , 'modified', wfTimestamp( TS_ISO_8601, $revision->getTimestamp() ) );

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
	 */
	public function output( EntityContent $entity, ApiFormatBase $printer  ) {
		// NOTE: The way the ApiResult is provided to $printer is somewhat
		//       counter-intuitive. Basically, the relevant ApiResult object
		//       is owned by the ApiMain module provided by getApiMain().

		// Pushes $entity into the ApiResult held by the ApiMain module
		$this->generateApiResult( $entity, $printer );

		$this->getOutput()->disable(); // don't generate HTML

		$printer->profileIn();
		$printer->initPrinter( false );

		// Outputs the ApiResult held by the ApiMain module
		$printer->execute();

		$printer->closePrinter();
		$printer->profileOut();

		//FIXME: Content-Length is bogus! Where does it come from?!
	}

	/**
	 * Output the entity data and set the appropriate HTTP response headers.
	 *
	 * @param Wikibase\EntityContent $entity the entity to output.
	 * @param ApiFormatBase $printer the printer to use to generate the output
	 *
	 * @throws HttpError If an unsupported format is requested.
	 */
	public function showData( EntityContent $entity, ApiFormatBase $printer ) {
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
		$contentType = $printer->getIsHtml() ? 'text/html' : $printer->getMimeType();
		$response->header( 'Content-Type: ' . $contentType . '; charset=UTF-8' );

		// allow the client to cache this
		$mode = 'public';
		$response->header( 'Cache-Control: ' . $mode . ', s-maxage=' . $smaxage . ', max-age=' . $maxage );
		//FIXME: Cache-Control and Expires headers are apparently overwritten later!

		$this->output( $entity, $printer );
	}

}
