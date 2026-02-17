<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiResult;

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

		$result = $this->graphQLService->query( $data['query'] ?? '', $variables, $operationName );
		$this->preserveAllKeys( $result );

		foreach ( $result as $resultKey => $value ) {
			$this->getResult()->addValue( null, $resultKey, $value );
		}
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

	/**
	 * Fields starting with an underscore get stripped from the response by the Action API by default, because that is the naming convention
	 * for metadata fields. This method adds a "_preservekeys" marker to all associative arrays to preserve introspection fields, and any
	 * field aliases starting with an underscore.
	 */
	private function preserveAllKeys( array &$value ): void {
		foreach ( $value as &$item ) {
			if ( is_array( $item ) ) {
				$this->preserveAllKeys( $item );
				if ( !array_is_list( $item ) ) {
					$item[ApiResult::META_PRESERVE_KEYS] = array_keys( $item );
				}
			}
		}
	}
}
