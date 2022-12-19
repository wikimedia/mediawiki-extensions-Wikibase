<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use Language;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\View\DefaultMetaTagsCreator;
use Wikibase\View\EntityMetaTagsCreator;

/**
 * @covers \Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityMetaTagsCreatorFactoryTest extends TestCase {

	public function testInvalidConstructorArgument() {
		$this->expectException( InvalidArgumentException::class );
		new DispatchingEntityMetaTagsCreatorFactory(
			[ 'invalid' ]
		);
	}

	public function testUnknownEntityType__returnsDefault() {
		$factory = new DispatchingEntityMetaTagsCreatorFactory(
			[]
		);

		$newMetaTags = $factory->newEntityMetaTags(
			'unknown',
			$this->createMock( Language::class )
		);

		$this->assertInstanceOf( DefaultMetaTagsCreator::class, $newMetaTags );
	}

	public function testNoEntityMetaTagsReturned() {
		$factory = new DispatchingEntityMetaTagsCreatorFactory(
			[
				'dummy-entity-type' => function() {
					return null;
				},
			]
		);

		$this->expectException( LogicException::class );
		$factory->newEntityMetaTags(
			'dummy-entity-type',
			$this->createMock( Language::class )
		);
	}

	public function testNewEntityMetaTags() {
		$language = $this->createMock( Language::class );

		$entityMetaTags = $this->createMock( EntityMetaTagsCreator::class );

		$factory = new DispatchingEntityMetaTagsCreatorFactory(
			[
				'foo' => function( $newLanguage )
				use (
					$entityMetaTags,
					$language
				){
					$this->assertSame( $language, $newLanguage );
					return $entityMetaTags;
				},
			]
		);

		$newEntityMetaTags = $factory->newEntityMetaTags(
			'foo',
			$language
		);

		$this->assertSame( $entityMetaTags, $newEntityMetaTags );
	}

}
