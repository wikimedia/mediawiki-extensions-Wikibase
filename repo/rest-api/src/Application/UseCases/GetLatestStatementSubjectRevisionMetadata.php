<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Services\StatementSubjectRevisionMetaDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetLatestStatementSubjectRevisionMetadata {

	private StatementSubjectRevisionMetadataRetriever $metadataRetriever;

	public function __construct( StatementSubjectRevisionMetaDataRetriever $metadataRetriever ) {
		$this->metadataRetriever = $metadataRetriever;
	}

	/**
	 * @throws ItemRedirect if the item is a redirect
	 * @throws UseCaseError if the subject does not exist
	 *
	 * @return array{int, string}
	 */
	public function execute( StatementGuid $statementId ): array {
		$metaDataResult = $this->metadataRetriever->getLatestRevisionMetadata( $statementId );

		if ( !$metaDataResult->subjectExists() ) {
			throw new UseCaseError(
				UseCaseError::STATEMENT_NOT_FOUND,
				"Could not find a statement with the ID: $statementId"
			);
		}

		if ( $metaDataResult->isRedirect() ) {
			throw new ItemRedirect( $metaDataResult->getRedirectTarget()->getSerialization() );
		}

		return [ $metaDataResult->getRevisionId(), $metaDataResult->getRevisionTimestamp() ];
	}

}
