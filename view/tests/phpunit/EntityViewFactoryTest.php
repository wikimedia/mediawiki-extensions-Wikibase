<?php

namespace Wikibase\Test;

use DataTypes\DataTypeFactory;
use PHPUnit_Framework_TestCase;
use SiteList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\EntityViewFactory;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

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
 * @author Thiemo Mättig
 */
class EntityViewFactoryTest extends PHPUnit_Framework_TestCase {

	private function newEntityViewFactory(
		EntityIdFormatterFactory $htmlFactory = null,
		EntityIdFormatterFactory $plainFactory = null
	) {
		$templateFactory = new TemplateFactory( new TemplateRegistry( array() ) );

		$languageNameLookup = $this->getMock( 'Wikibase\Lib\LanguageNameLookup' );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		return new EntityViewFactory(
			$htmlFactory ?: $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_HTML ),
			$plainFactory ?: $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_PLAIN ),
			$this->getSnakFormatterFactory(),
			$this->getSiteStore(),
			new DataTypeFactory( array() ),
			$templateFactory,
			$languageNameLookup,
			array(),
			array(),
			array()
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException(
		EntityIdFormatterFactory $htmlFormatterFactory,
		EntityIdFormatterFactory $plainFormatterFactory
	) {
		$this->setExpectedException( 'InvalidArgumentException' );
		$this->newEntityViewFactory( $htmlFormatterFactory, $plainFormatterFactory );
	}

	public function invalidConstructorArgumentsProvider() {
		$htmlFactory = $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_HTML );
		$plainFactory = $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_PLAIN );
		$wikiFactory = $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_WIKI );

		return array(
			array( $wikiFactory, $plainFactory ),
			array( $htmlFactory, $wikiFactory ),
		);
	}

	/**
	 * @dataProvider newEntityViewProvider
	 */
	public function testNewEntityView( $expectedClass, $entityType ) {
		$languageFallback = new LanguageFallbackChain( array() );

		$entityView = $this->newEntityViewFactory()->newEntityView(
			$entityType,
			'de',
			$this->getMock( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' ),
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
		$languageFallback = new LanguageFallbackChain( array() );

		$this->setExpectedException( 'InvalidArgumentException' );

		$this->newEntityViewFactory()->newEntityView(
			'kittens',
			'de',
			$this->getMock( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' ),
			$languageFallback,
			$this->getMock( 'Wikibase\View\EditSectionGenerator' )
		);
	}

	private function getEntityIdFormatterFactory( $format ) {
		$entityIdFormatter = $this->getMock( 'Wikibase\DataModel\Services\EntityId\EntityIdFormatter' );

		$formatterFactory = $this->getMock( 'Wikibase\View\EntityIdFormatterFactory' );

		$formatterFactory->expects( $this->any() )
			->method( 'getOutputFormat' )
			->will( $this->returnValue( $format ) );

		$formatterFactory->expects( $this->any() )
			->method( 'getEntityIdFormatter' )
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
