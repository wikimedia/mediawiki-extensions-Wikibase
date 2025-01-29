<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
class AssertStatementSubjectExists {

	private GetLatestStatementSubjectRevisionMetadata $getRevisionMetadata;

	public function __construct( GetLatestStatementSubjectRevisionMetadata $getRevisionMetadata ) {
		$this->getRevisionMetadata = $getRevisionMetadata;
	}

	/**
	 * @throws ItemRedirect if the statement subject is a redirect
	 * @throws UseCaseError if the statement subject does not exist
	 */
	public function execute( StatementGuid $statementId ): void {
		$this->getRevisionMetadata->execute( $statementId );
	}

}
