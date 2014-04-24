<?php

namespace Wikibase\Test;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\ChangeOp\ItemChangeOpFactory;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;

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

	public function testGetChangeOpFactory() {
		$factory = $this->newChangeOpFactoryProvider()->getChangeOpFactory( Property::ENTITY_TYPE );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpFactory', $factory );
	}

	public function testGetItemChangeOpFactory() {
		$actual = $this->newChangeOpFactoryProvider()->getItemChangeOpFactory( Property::ENTITY_TYPE );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpFactory', $actual );

		$expected = $this->newChangeOpFactoryProvider()->getChangeOpFactory( Item::ENTITY_TYPE );
		$this->assertEquals( get_class( $expected ), get_class( $actual ) );
	}

}
