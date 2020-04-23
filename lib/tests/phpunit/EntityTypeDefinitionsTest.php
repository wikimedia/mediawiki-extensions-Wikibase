<?php

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers \Wikibase\Lib\EntityTypeDefinitions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityTypeDefinitionsTest extends \PHPUnit\Framework\TestCase {

	public function testGet() {
		$definitions = new EntityTypeDefinitions( [
			'foo' => [
				'some-field' => 'foo-field',
				'some-other-field' => 'foo-other-field',
			],
			'bar' => [
				'some-field' => 'bar-field',
			],
			'baz' => [],
		] );

		$this->assertEquals(
			[ 'foo' => 'foo-field', 'bar' => 'bar-field' ],
			$definitions->get( 'some-field' )
		);
	}

	public function testGetEntityIdBuilders() {
		$definitions = new EntityTypeDefinitions( [
			'foo' => [
				EntityTypeDefinitions::ENTITY_ID_PATTERN => 'foo-id-pattern',
				EntityTypeDefinitions::ENTITY_ID_BUILDER => 'new-foo-id',
			],
		] );

		$this->assertSame( [
			'foo-id-pattern' => 'new-foo-id',
		], $definitions->getEntityIdBuilders() );
	}

}
