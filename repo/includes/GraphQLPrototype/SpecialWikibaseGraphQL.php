<?php

namespace Wikibase\Repo\GraphQLPrototype;

use MediaWiki\SpecialPage\SpecialPage;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class SpecialWikibaseGraphQL extends SpecialPage {

	public const SPECIAL_PAGE_NAME = 'WikibaseGraphQL';
	private GraphQLQueryService $graphQLService;

	public function __construct(
		PrefetchingTermLookup $termLookup,
		ContentLanguages $termLanguages,
	) {
		parent::__construct( self::SPECIAL_PAGE_NAME, listed: false );
		$this->graphQLService = new GraphQLQueryService( $termLanguages, $termLookup );
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

		$output = $this->graphQLService->query( $input['query'] ?? '' );

		$this->getOutput()->disable();
		$response = $this->getRequest()->response();
		$response->header( 'Access-Control-Allow-Origin: *' );
		$response->header( 'Content-Type: application/json' );

		print \FormatJson::encode( $output, pretty: true, escaping: \FormatJson::ALL_OK );
	}
}
