<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementRevision;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryStatementRepository implements StatementWriteModelRetriever, StatementUpdater, StatementRemover {
	use StatementReadModelHelper;

	private array $statements = [];
	private array $latestRevisionData = [];

	public function addStatement( Statement $statement ): void {
		if ( !$statement->getGuid() ) {
			throw new LogicException( 'Test statement must have an ID.' );
		}

		$this->statements[$statement->getGuid()] = $statement;
	}

	public function getLatestRevisionId( StatementGuid $id ): int {
		return $this->latestRevisionData["$id"]['revId'];
	}

	public function getLatestRevisionTimestamp( StatementGuid $id ): string {
		return $this->latestRevisionData["$id"]['revTime'];
	}

	public function getLatestRevisionEditMetadata( StatementGuid $id ): EditMetadata {
		return $this->latestRevisionData["$id"]['editMetadata'];
	}

	public function update( Statement $statement, EditMetadata $editMetadata ): StatementRevision {
		$this->statements[$statement->getGuid()] = $statement;
		$revisionData = $this->generateRevisionData( $editMetadata );
		$this->latestRevisionData[$statement->getGuid()] = $revisionData;

		return new StatementRevision(
			$this->newStatementReadModelConverter()->convert( $statement ),
			$revisionData['revTime'],
			$revisionData['revId']
		);
	}

	public function remove( StatementGuid $id, EditMetadata $editMetadata ): void {
		unset( $this->statements["$id"] );
		$this->latestRevisionData["$id"] = $this->generateRevisionData( $editMetadata );
	}

	public function getStatementWriteModel( StatementGuid $id ): ?Statement {
		return $this->statements["$id"] ?? null;
	}

	private function generateRevisionData( EditMetadata $editMetadata ): array {
		return [
			'revId' => rand(),
			// using the real date/time here is a bit dangerous, but should be ok as long as revId is also checked.
			'revTime' => date( 'YmdHis' ),
			'editMetadata' => $editMetadata,
		];
	}
}
