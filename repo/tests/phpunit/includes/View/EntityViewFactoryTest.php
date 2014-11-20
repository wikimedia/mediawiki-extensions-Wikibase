<?php

namespace Wikibase\Test;

use Language;
use ParserOptions;
use TestUser;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\View\EntityViewFactory;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider newEntityViewProvider
	 */
	public function testNewEntityView( $expectedClass, $entityType ) {
		$entityViewFactory = $this->getEntityViewFactory();

		$entityView = $entityViewFactory->newEntityView(
			new LanguageFallbackChain( array() ),
			'de',
			$entityType,
			$this->getLabelLookupFactory()
		);

		$this->assertInstanceOf( $expectedClass, $entityView );
	}

	public function newEntityViewProvider() {
		return array(
			array( 'Wikibase\ItemView', 'item' ),
			array( 'Wikibase\PropertyView', 'property' )
		);
	}

	public function testNewEntityView_withInvalidType() {
		$entityViewFactory = $this->getEntityViewFactory();

		$this->setExpectedException( 'InvalidArgumentException' );

		$entityViewFactory->newEntityView(
			new LanguageFallbackChain( array() ),
			'de',
			'kittens',
			$this->getLabelLookupFactory()
		);
	}

	private function getEntityViewFactory() {
		$self = $this;
		return new EntityViewFactory(
			$this->getEntityTitleLookup(),
			new MockRepository(),
			function() use ( $self ) {
				return $self->getSnakFormatterFactory();
			}
		);
	}

	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$name = $id->getEntityType() . ':' . $id->getSerialization();
				return Title::makeTitle( NS_MAIN, $name );
			} ) );

		return $entityTitleLookup;
	}

	private function getSnakFormatterFactory() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		$snakFormatterFactory = $this->getMockBuilder( 'Wikibase\Lib\OutputFormatSnakFormatterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $snakFormatter ) );

		return $snakFormatterFactory;
	}

	private function getLabelLookupFactory() {
		return $this->getMock( 'Wikibase\Lib\Store\LabelLookupFactory' );
	}

}
