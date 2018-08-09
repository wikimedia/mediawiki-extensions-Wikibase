<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
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
			$this->getMockLanguageFallbackChain()
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
			$this->getMockLanguageFallbackChain()
		);
	}

	public function testNewEntityMetaTags() {
		$languageFallbackChain = $this->getMockLanguageFallbackChain();

		$factory = new DispatchingEntityMetaTagsFactory(
			[
				'foo' => function()
				{
					return MockEntityMetaTags::class;
				}
			]
		);

		$newEntityMetaTags = $factory->newEntityMetaTags(
			'foo',
			$languageFallbackChain
		);

		$this->assertInstanceOf( MockEntityMetaTags::class, $newEntityMetaTags );
		$this->assertEquals( $newEntityMetaTags->constructParams[0], $languageFallbackChain );
	}

	private function getMockLanguageFallbackChain() {
		return $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
