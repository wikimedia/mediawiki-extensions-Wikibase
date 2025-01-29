<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementRevision;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRemover;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryStatementRepository implements StatementRetriever, StatementWriteModelRetriever, StatementUpdater, StatementRemover {
	use StatementReadModelHelper;

	private array $statements = [];
	private array $latestRevisionData = [];

	public function addStatement( StatementWriteModel $statement ): void {
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

	public function update( StatementWriteModel $statement, EditMetadata $editMetadata ): StatementRevision {
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

	public function getStatement( StatementGuid $id ): ?Statement {
		return $this->statements["$id"] ? $this->newStatementReadModelConverter()->convert( $this->statements["$id"] ) : null;
	}

	public function getStatementWriteModel( StatementGuid $id ): ?StatementWriteModel {
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
