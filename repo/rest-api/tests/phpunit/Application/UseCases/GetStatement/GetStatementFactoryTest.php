<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementFactory;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatementFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementFactoryTest extends TestCase {

	public function testNewGetStatement(): void {
		$requestedSubjectIdValidator = $this->createStub( RequestedSubjectIdValidator::class );
		$statementIdValidator = $this->createStub( StatementIdValidator::class );
		$statementRetriever = $this->createStub( StatementRetriever::class );
		$latestRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );

		$getStatementFactory = new GetStatementFactory( $statementIdValidator, $statementRetriever, $latestRevisionMetadata );
		$this->assertEquals(
			new GetStatement(
				new GetStatementValidator( $statementIdValidator, $requestedSubjectIdValidator ),
				$statementRetriever,
				$latestRevisionMetadata
			),
			$getStatementFactory->newGetStatement( $requestedSubjectIdValidator )
		);
	}

}
