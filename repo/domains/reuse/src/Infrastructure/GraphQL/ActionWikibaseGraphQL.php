<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;

/**
 * @license GPL-2.0-or-later
 */
class ActionWikibaseGraphQL extends ApiBase {

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		private readonly GraphQLService $graphQLService,
	) {
		parent::__construct( $mainModule, $moduleName );
	}

	public function execute(): void {
		$rawBody = $this->getRequest()->getRawPostString();
		$data = json_decode( $rawBody, true );

		$variables = isset( $data['variables'] ) && is_array( $data['variables'] ) ? $data['variables'] : [];
		$operationName = isset( $data['operationName'] ) && is_string( $data['operationName'] ) ? $data['operationName'] : null;

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			$this->graphQLService->query( $data['query'] ?? '', $variables, $operationName )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [];
	}
}
