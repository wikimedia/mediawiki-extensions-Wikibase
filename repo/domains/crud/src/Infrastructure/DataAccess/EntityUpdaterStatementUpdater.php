<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementRevision;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdateFailed;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\StatementSubjectDisappeared;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterStatementUpdater implements StatementUpdater {

	private StatementGuidParser $statementIdParser;
	private StatementSubjectRetriever $statementSubjectRetriever;
	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		StatementGuidParser $statementIdParser,
		StatementSubjectRetriever $statementSubjectRetriever,
		EntityUpdater $entityUpdater,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->statementIdParser = $statementIdParser;
		$this->statementSubjectRetriever = $statementSubjectRetriever;
		$this->entityUpdater = $entityUpdater;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	/**
	 * @throws EntityUpdateFailed
	 * @throws StatementSubjectDisappeared
	 */
	public function update( Statement $statement, EditMetadata $editMetadata ): StatementRevision {
		if ( $statement->getGuid() === null ) {
			throw new LogicException( 'Statement ID should not be null' );
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable statement id exists
		$statementId = $this->statementIdParser->parse( $statement->getGuid() );
		$subject = $this->statementSubjectRetriever->getStatementSubject( $statementId->getEntityId() );

		// if $subject is null, then it went missing between the use case
		// subject assertion and invoking the statement updater by the use case
		if ( $subject === null ) {
			throw new StatementSubjectDisappeared();
		}

		$subject->getStatements()->replaceStatement( $statementId, $statement );

		$entityRevision = $this->entityUpdater->update( $subject, $editMetadata );

		return new StatementRevision(
			$this->statementReadModelConverter->convert(
				// @phan-suppress-next-line PhanUndeclaredMethod
				$entityRevision->getEntity()->getStatements()->getFirstStatementWithGuid( $statement->getGuid() )
			),
			$entityRevision->getTimestamp(),
			$entityRevision->getRevisionId()
		);
	}

}
