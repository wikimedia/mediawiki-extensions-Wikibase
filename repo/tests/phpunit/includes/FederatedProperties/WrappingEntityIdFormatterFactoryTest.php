<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Test\FederatedProperties;

use Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\FederatedProperties\WrappingEntityIdFormatterFactory;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * @covers \Wikibase\Repo\FederatedProperties\WrappingEntityIdFormatterFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class WrappingEntityIdFormatterFactoryTest extends TestCase {

	/**
	 * @return EntityIdFormatterFactory|MockObject
	 */
	private function mockEntityIdFormatterFactory() {
		$mock = $this->createMock( EntityIdFormatterFactory::class );
		$mock->method( 'getOutputFormat' )
			->willReturn( 'OUTPUT' );
		$mock->method( 'getEntityIdFormatter' )
			->with( $this->createMock( Language::class ) )
			->willReturn( $this->createMock( EntityIdFormatter::class ) );
		return $mock;
	}

	public function testGetOutputFormat() {
		$factory = new WrappingEntityIdFormatterFactory( $this->mockEntityIdFormatterFactory() );
		$this->assertEquals(
			'OUTPUT',
			$factory->getOutputFormat()
		);
	}

	public function testGetEntityIdFormatter() {
		$factory = new WrappingEntityIdFormatterFactory( $this->mockEntityIdFormatterFactory() );
		$formatter = $factory->getEntityIdFormatter( $this->createMock( Language::class ) );
		$this->assertInstanceOf(
			EntityIdFormatter::class,
			$formatter
		);
	}

}
