<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use HashSiteStore;
use TestSites;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Merge\MergeFactory;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeOpFactoryProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	protected $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	/**
	 * @return ChangeOpFactoryProvider
	 */
	protected function newChangeOpFactoryProvider() {
		$entityId = new ItemId( 'Q2' );

		$constraintProvider = new EntityConstraintProvider(
			$this->mockProvider->getMockSiteLinkConflictLookup(),
			$this->createMock( TermValidatorFactory::class ),
			[]
		);

		return new ChangeOpFactoryProvider(
			$constraintProvider,
			new GuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $entityId ),
			$this->mockProvider->getMockSnakValidator(),
			$this->mockProvider->getMockTermValidatorFactory(),
			new HashSiteStore( TestSites::getSites() ),
			$this->mockProvider->getMockSnakNormalizer(),
			$this->mockProvider->getMockReferenceNormalizer(),
			$this->mockProvider->getMockStatementNormalizer(),
			[],
			true
		);
	}

	public function testGetFingerprintChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getFingerprintChangeOpFactory();
		$this->assertInstanceOf( FingerprintChangeOpFactory::class, $factory );
	}

	public function testGetStatementChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getStatementChangeOpFactory();
		$this->assertInstanceOf( StatementChangeOpFactory::class, $factory );
	}

	public function testGetSiteLinkChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getSiteLinkChangeOpFactory();
		$this->assertInstanceOf( SiteLinkChangeOpFactory::class, $factory );
	}

	public function testGetMergeFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getMergeFactory();
		$this->assertInstanceOf( MergeFactory::class, $factory );
	}

}
