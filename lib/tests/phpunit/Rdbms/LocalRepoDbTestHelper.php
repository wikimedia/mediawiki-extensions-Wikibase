<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use IDatabase;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * Trait to be used in Lib integration tests to get a RepoDbFactory where we cannot use the repo/client service getters.
 * @license GPL-2.0-or-later
 */
trait LocalRepoDbTestHelper {

	public function getRepoDomainDbFactoryForDb( IDatabase $db ): RepoDomainDbFactory {
		$lbFactory = LBFactorySingle::newFromConnection( $db );
		$domainId = $lbFactory->getLocalDomainID();

		return new RepoDomainDbFactory(
			$lbFactory,
			$domainId,
			$this->newStubEntitySourcesForDomain( $domainId )
		);
	}

	private function newStubEntitySourcesForDomain( string $dbName ): EntitySourceDefinitions {
		/** @var $this TestCase */
		$entitySource = $this->createStub( EntitySource::class );
		$entitySource->method( 'getDatabaseName' )->willReturn( $dbName );

		$entitySourceDefinitions = $this->createMock( EntitySourceDefinitions::class );
		$entitySourceDefinitions->method( 'getSourceForEntityType' )->willReturn( $entitySource );

		return $entitySourceDefinitions;
	}

}
