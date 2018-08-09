<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use Language;
use LogicException;
use OutOfBoundsException;
use PHPUnit4And6Compat;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsFactory
 *
 * @group Wikibase
 *
 * @license GNU GPL v2+
 */
class DispatchingEntityMetaTagsFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstructorArgument() {
		new DispatchingEntityMetaTagsFactory(
			[ 'invalid' ]
		);
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testUnknownEntityType() {
		$factory = new DispatchingEntityMetaTagsFactory(
			[]
		);

		$factory->newEntityMetaTags(
			'unknown',
			$this->getMockLanguage()
		);
	}

	/**
	 * @expectedException LogicException
	 */
	public function testNoEntityMetaTagsReturned() {
		$factory = new DispatchingEntityMetaTagsFactory(
			[
				'dummy-entity-type' => function() {
					return null;
				}
			]
		);

		$factory->newEntityMetaTags(
			'dummy-entity-type',
			$this->getMockLanguage()
		);
	}

	public function testNewEntityMetaTags() {
		$language = $this->getMockLanguage();
		$languageFallbackChain = new LanguageFallbackChain( [ $language ] );

		$entityMetaTags = new MockEntityMetaTags( $languageFallbackChain );

		$factory = new DispatchingEntityMetaTagsFactory(
			[
				'foo' => function( $language ) use ( $entityMetaTags )
				{
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

	private function getMockLanguage() {
		return $this->getMockBuilder( Language::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
