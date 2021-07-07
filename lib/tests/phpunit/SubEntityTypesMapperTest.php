<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\Lib\SubEntityTypesMapper
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SubEntityTypesMapperTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider parentTypeProvider
	 */
	public function testGetParentEntityType( array $typeMap, string $givenType, ?string $parent ): void {
		$map = new SubEntityTypesMapper( $typeMap );

		$this->assertSame( $parent, $map->getParentEntityType( $givenType ) );
	}

	public function parentTypeProvider() {
		yield 'top level type without a parent' => [
			'typeMap' => [ 'property' => [] ],
			'givenType' => 'property',
			'parent' => null,
		];

		yield 'sub entity type' => [
			'typeMap' => [ 'property' => [], 'lexeme' => [ 'form', 'sense' ] ],
			'givenType' => 'form',
			'parent' => 'lexeme',
		];

		yield 'unknown type' => [
			'typeMap' => [ 'property' => [] ],
			'givenType' => 'potato',
			'parent' => null,
		];
	}

}
