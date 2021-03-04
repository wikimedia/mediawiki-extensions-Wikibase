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
		$mock->expects( $this->any() )
		->method( 'getOutputFormat' )
		->willReturn( 'OUTPUT' );
		$mock->expects( $this->any() )
		->method( 'getEntityIdFormatter' )
		->with( $this->mockLanguage() )
		->willReturn( $this->createMock( EntityIdFormatter::class ) );
		return $mock;
	}

	/**
	 * @return Language|MockObject
	 */
	private function mockLanguage() {
		return $this->createMock( Language::class );
	}

	private function newWrappingEntityIdFormatterFactory() : WrappingEntityIdFormatterFactory {
		return new WrappingEntityIdFormatterFactory( $this->mockEntityIdFormatterFactory() );
	}

	public function testGetOutputFormat() {
		$this->assertEquals(
			'OUTPUT',
			$this->newWrappingEntityIdFormatterFactory()->getOutputFormat()
		);
	}

	public function testGetEntityIdFormatter() {
		$this->assertInstanceOf(
			EntityIdFormatter::class,
			$this->newWrappingEntityIdFormatterFactory()->getEntityIdFormatter( $this->mockLanguage() )
		);
	}

}
