<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use SiteList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\EntityViewFactory;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\EntityViewFactory
 *
 * @uses Wikibase\View\ClaimHtmlGenerator
 * @uses Wikibase\View\EditSectionGenerator
 * @uses Wikibase\View\EntityTermsView
 * @uses Wikibase\View\EntityView
 * @uses Wikibase\View\ItemView
 * @uses Wikibase\View\PropertyView
 * @uses Wikibase\View\SiteLinksView
 * @uses Wikibase\View\StatementGroupListView
 * @uses Wikibase\View\SnakHtmlGenerator
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 * @uses Wikibase\View\TextInjector
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider newEntityViewProvider
	 */
	public function testNewEntityView( $expectedClass, $entityType ) {
		$entityViewFactory = $this->getEntityViewFactory();

		$languageFallback = new LanguageFallbackChain( array() );

		$entityView = $entityViewFactory->newEntityView(
			$entityType,
			'de',
			$this->getMock( 'Wikibase\Lib\Store\LabelDescriptionLookup' ),
			$languageFallback,
			$this->getMock( 'Wikibase\View\EditSectionGenerator' )
		);

		$this->assertInstanceOf( $expectedClass, $entityView );
	}

	public function newEntityViewProvider() {
		return array(
			array( 'Wikibase\View\ItemView', 'item' ),
			array( 'Wikibase\View\PropertyView', 'property' )
		);
	}

	public function testNewEntityView_withInvalidType() {
		$entityViewFactory = $this->getEntityViewFactory();

		$languageFallback = new LanguageFallbackChain( array() );

		$this->setExpectedException( 'InvalidArgumentException' );

		$entityViewFactory->newEntityView(
			'kittens',
			'de',
			$this->getMock( 'Wikibase\Lib\Store\LabelDescriptionLookup' ),
			$languageFallback,
			$this->getMock( 'Wikibase\View\EditSectionGenerator' )
		);
	}

	private function getEntityViewFactory() {
		$templateFactory = TemplateFactory::getDefaultInstance();

		return new EntityViewFactory(
			$this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_HTML ),
			$this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_PLAIN ),
			$this->getSnakFormatterFactory(),
			$this->getSiteStore(),
			$this->getMock( 'DataTypes\DataTypeFactory' ),
			$templateFactory,
			$this->getMock( 'Wikibase\Lib\LanguageNameLookup' )
		);
	}

	private function getEntityIdFormatterFactory( $format ) {
		$entityIdFormatter = $this->getMock( 'Wikibase\Lib\EntityIdFormatter' );

		$formatterFactory = $this->getMock( 'Wikibase\View\EntityIdFormatterFactory' );

		$formatterFactory->expects( $this->any() )
			->method( 'getOutputFormat' )
			->will( $this->returnValue( $format ) );

		$formatterFactory->expects( $this->any() )
			->method( 'getEntityIdFormater' )
			->will( $this->returnValue( $entityIdFormatter ) );

		return $formatterFactory;
	}

	private function getSnakFormatterFactory() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		$snakFormatterFactory = $this->getMock( 'Wikibase\View\HtmlSnakFormatterFactory' );

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $snakFormatter ) );

		return $snakFormatterFactory;
	}

	private function getSiteStore() {
		$siteStore = $this->getMock( 'SiteStore' );

		$siteStore->expects( $this->any() )
			->method( 'getSites' )
			->will( $this->returnValue( new SiteList() ) );

		return $siteStore;
	}

}
