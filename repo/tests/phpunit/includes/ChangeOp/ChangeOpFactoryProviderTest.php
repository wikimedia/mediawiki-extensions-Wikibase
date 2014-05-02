<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\ChangeOp\ChangeOpFactoryProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeOpFactoryProviderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	protected $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	/**
	 * @return ChangeOpFactoryProvider
	 */
	protected function newChangeOpFactoryProvider() {
		$entityId = new ItemId( 'Q2' );

		return new ChangeOpFactoryProvider(
			$this->mockProvider->getMockLabelDescriptionDuplicateDetector(),
			$this->mockProvider->getMockSitelinkCache(),
			$this->mockProvider->getMockGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $entityId ),
			$this->mockProvider->getMockSnakValidator()
		);
	}

	public function testGetFingerprintChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getFingerprintChangeOpFactory();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\FingerprintChangeOpFactory', $factory );
	}

	public function testGetClaimChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getClaimChangeOpFactory();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ClaimChangeOpFactory', $factory );
	}

	public function testGetStatementChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getStatementChangeOpFactory();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\StatementChangeOpFactory', $factory );
	}

	public function testGetSiteLinkChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getSiteLinkChangeOpFactory();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\SiteLinkChangeOpFactory', $factory );
	}

	public function testGetMergeChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getMergeChangeOpFactory();
		$this->assertInstanceOf( 'Wikibase\ChangeOp\MergeChangeOpsFactory', $factory );
	}

}
