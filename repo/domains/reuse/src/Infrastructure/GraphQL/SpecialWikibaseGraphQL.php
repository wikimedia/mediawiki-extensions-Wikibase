<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use FormatJson;
use MediaWiki\SpecialPage\SpecialPage;

/**
 * @license GPL-2.0-or-later
 */
class SpecialWikibaseGraphQL extends SpecialPage {

	public const SPECIAL_PAGE_NAME = 'WikibaseGraphQL';

	public function __construct( private readonly GraphQLService $graphQLService ) {
		parent::__construct( self::SPECIAL_PAGE_NAME, listed: false );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$input = json_decode(
			$this->getRequest()->getRawInput(),
			true
		);

		$variables = isset( $input['variables'] ) && is_array( $input['variables'] ) ? $input['variables'] : [];
		$operationName = isset( $input['operationName'] ) && is_string( $input['operationName'] ) ? $input['operationName'] : null;
		$output = $this->graphQLService->query( $input['query'] ?? '', $variables, $operationName );

		$this->getOutput()->disable();
		$response = $this->getRequest()->response();
		$response->header( 'Access-Control-Allow-Origin: *' );
		$response->header( 'Content-Type: application/json' );

		print FormatJson::encode( $output, pretty: true, escaping: FormatJson::ALL_OK );
	}
}
