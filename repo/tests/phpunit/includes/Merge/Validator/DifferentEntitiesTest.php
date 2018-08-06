<?php

namespace Wikibase\Lexeme\Tests\Merge\Validator;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Merge\Validator\DifferentEntities;

/**
 * @covers \Wikibase\Repo\Merge\Validator\DifferentEntities
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DifferentEntitiesTest extends TestCase {

	/**
	 * @dataProvider provideSamples
	 */
	public function testValidate( $expected, $source, $target ) {
		$validator = new DifferentEntities();

		$this->assertSame( $expected, $validator->validate( $source, $target ) );
	}

	public function provideSamples() {
		yield [
			true,
			$this->getMockEntity( 'E1' ),
			$this->getMockEntity( 'E2' ),
		];

		yield [
			false,
			$this->getMockEntity( 'E1' ),
			$this->getMockEntity( 'E1' )
		];

		$e1 = $this->getMockEntity( 'E1' );
		yield [
			false,
			$e1,
			$e1,
		];

		yield [
			false,
			$this->getMockEntity( null ),
			$this->getMockEntity( 'E2' ),
		];
	}

	private function getMockEntity( $id ) {
		$e1 = $this->getMockBuilder( EntityDocument::class )->getMock();
		$e1->method( 'getId' )
			->willReturn(
				is_null( $id ) ?
					null :
					$this->getMockForAbstractClass( EntityId::class, [ $id ] )
			);

		return $e1;
	}

}
