<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplaceStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementFactory;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceStatementFactoryTest extends TestCase {

	public function testNewReplaceStatement(): void {
		$requestedSubjectIdValidator = $this->createStub( RequestedSubjectIdValidator::class );
		$statementIdValidator = $this->createStub( StatementIdValidator::class );
		$statementValidator = $this->createStub( StatementValidator::class );
		$editMetadataValidator = $this->createStub( EditMetadataValidator::class );
		$statementIdParser = $this->createStub( StatementGuidParser::class );
		$entityIdParser = $this->createStub( EntityIdParser::class );
		$assertStatementSubjectExists = $this->createStub( AssertStatementSubjectExists::class );
		$assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$statementUpdater = $this->createStub( StatementUpdater::class );

		$replaceStatementFactory = new ReplaceStatementFactory(
			$statementIdValidator,
			$statementValidator,
			$editMetadataValidator,
			$statementIdParser,
			$entityIdParser,
			$assertStatementSubjectExists,
			$assertUserIsAuthorized,
			$statementUpdater
		);
		$this->assertEquals(
			new ReplaceStatement(
				new ReplaceStatementValidator(
					$requestedSubjectIdValidator,
					$statementIdValidator,
					$statementValidator,
					$editMetadataValidator
				),
				$statementIdParser,
				$entityIdParser,
				$assertStatementSubjectExists,
				$assertUserIsAuthorized,
				$statementUpdater
			),
			$replaceStatementFactory->newReplaceStatement( $requestedSubjectIdValidator )
		);
	}

}
