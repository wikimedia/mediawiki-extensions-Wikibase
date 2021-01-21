<?php

namespace Wikibase\Lib\Tests;

use ReflectionClass;
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

	/** @dataProvider provideFieldNames */
	public function testFieldDocumented( string $fieldName ) {
		$documentation = $this->getEntityTypesDocumentation();
		// don’t use $this->assertStringContainsString(), it prints the whole haystack each time
		if ( strpos( $documentation, $fieldName ) === false ) {
			$this->fail( "No documentation found in entitytypes.md for '$fieldName'" );
		} else {
			$this->addToAssertionCount( 1 );
		}
	}

	public function provideFieldNames() {
		$class = new ReflectionClass( EntityTypeDefinitions::class );
		// TODO PHP8: use $class->getConstants( ReflectionClassConstant::IS_PUBLIC )
		foreach ( $class->getReflectionConstants() as $constant ) {
			if ( $constant->isPublic() ) {
				yield $constant->getName() => [ $constant->getValue() ];
			}
		}
	}

	private function getEntityTypesDocumentation(): string {
		static $documentation = null;
		if ( $documentation === null ) {
			$documentation = file_get_contents( __DIR__ . '/../../../docs/topics/entitytypes.md' );
		}
		return $documentation;
	}

}
