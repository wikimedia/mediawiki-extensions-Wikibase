<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use Language;
use PHPUnit4And6Compat;
use PHPUnit_Framework_MockObject_MockObject;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use PHPUnit\Framework\TestCase;
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
	use PHPUnit4And6Compat;

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstructorArgument() {
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
			$this->getMockLanguage()
		);

		$this->assertInstanceOf( DefaultMetaTagsCreator::class, $newMetaTags );
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|Language
	 */
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

		$this->setExpectedException( 'LogicException' );
		$factory->newEntityMetaTags(
			'dummy-entity-type',
			$this->getMockLanguage()
		);
	}

	public function testNewEntityMetaTags() {
		$language = $this->getMockLanguage();

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
