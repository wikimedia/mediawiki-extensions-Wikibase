<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDataTypesHandler extends SimpleHandler {

	private DataTypeDefinitions $dataTypeDefinitions;

	public function __construct( DataTypeDefinitions $dataTypeDefinitions ) {
		$this->dataTypeDefinitions = $dataTypeDefinitions;
	}

	public static function factory(): Handler {
		return new self( WikibaseRepo::getDataTypeDefinitions() );
	}

	public function run(): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream( json_encode( $this->dataTypeDefinitions->getValueTypes() ) ) );

		return $httpResponse;
	}

	public function needsWriteAccess(): bool {
		return false;
	}
}
