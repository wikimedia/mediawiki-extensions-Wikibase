<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use Language;
use OutOfBoundsException;
use PHPUnit4And6Compat;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory
 *
 * @group Wikibase
 *
 * @license GNU GPL v2+
 */
class DispatchingEntityMetaTagsCreatorFactoryTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstructorArgument() {
		new DispatchingEntityMetaTagsCreatorFactory(
			[ 'invalid' ]
		);
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testUnknownEntityType() {
		$factory = new DispatchingEntityMetaTagsCreatorFactory(
			[]
		);

		$factory->newEntityMetaTags(
			'unknown',
			$this->getMockLanguage()
		);
	}

	private function getMockLanguage() {
		return $this->createMock( Language::class );
	}

	public function testNoEntityMetaTagsReturned() {
		$factory = new DispatchingEntityMetaTagsCreatorFactory(
			[
				'dummy-entity-type' => function() {
					return null;
				}
			]
		);

		$this->expectException( 'LogicException' );
		$factory->newEntityMetaTags(
			'dummy-entity-type',
			$this->getMockLanguage()
		);
	}

	public function testNewEntityMetaTags() {
		$language = $this->getMockLanguage();
		$languageFallbackChain = new LanguageFallbackChain( [ $language ] );

		$entityMetaTags = new StubEntityMetaTagsCreator( $languageFallbackChain );

		$factory = new DispatchingEntityMetaTagsCreatorFactory(
			[
				'foo' => function( $language )
				use (
					$entityMetaTags
				){
					return $entityMetaTags;
				}
			]
		);

		$newEntityMetaTags = $factory->newEntityMetaTags(
			'foo',
			$language
		);

		$this->assertSame( $entityMetaTags, $newEntityMetaTags );
	}

}
