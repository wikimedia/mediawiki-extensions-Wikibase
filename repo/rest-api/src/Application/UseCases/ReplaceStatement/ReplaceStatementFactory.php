<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceStatementFactory {

	private StatementIdValidator $statementIdValidator;
	private StatementValidator $statementValidator;
	private EditMetadataValidator $editMetadataValidator;
	private StatementGuidParser $statementIdParser;
	private EntityIdParser $entityIdParser;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private StatementUpdater $statementUpdater;

	public function __construct(
		StatementIdValidator $statementIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator,
		StatementGuidParser $statementIdParser,
		EntityIdParser $entityIdParser,
		AssertStatementSubjectExists $assertStatementSubjectExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		StatementUpdater $statementUpdater
	) {
		$this->statementIdValidator = $statementIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
		$this->statementIdParser = $statementIdParser;
		$this->entityIdParser = $entityIdParser;
		$this->assertStatementSubjectExists = $assertStatementSubjectExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->statementUpdater = $statementUpdater;
	}

	public function newReplaceStatement( RequestedSubjectIdValidator $requestedSubjectIdValidator ): ReplaceStatement {
		return new ReplaceStatement(
			new ReplaceStatementValidator(
				$requestedSubjectIdValidator,
				$this->statementIdValidator,
				$this->statementValidator,
				$this->editMetadataValidator
			),
			$this->statementIdParser,
			$this->entityIdParser,
			$this->assertStatementSubjectExists,
			$this->assertUserIsAuthorized,
			$this->statementUpdater
		);
	}

}
