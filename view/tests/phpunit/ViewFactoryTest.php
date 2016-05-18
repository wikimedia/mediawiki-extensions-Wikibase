<?php

namespace Wikibase\View\Tests;

use DataTypes\DataTypeFactory;
use HashSiteStore;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use ValueFormatters\BasicNumberLocalizer;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikibase\View\HtmlSnakFormatterFactory;
use Wikibase\View\ItemView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\PropertyView;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\ViewFactory;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\ViewFactory
 *
 * @uses Wikibase\View\ClaimHtmlGenerator
 * @uses Wikibase\View\EditSectionGenerator
 * @uses Wikibase\View\EntityTermsView
 * @uses Wikibase\View\EntityView
 * @uses Wikibase\View\ItemView
 * @uses Wikibase\View\PropertyView
 * @uses Wikibase\View\SiteLinksView
 * @uses Wikibase\View\SnakHtmlGenerator
 * @uses Wikibase\View\StatementGroupListView
 * @uses Wikibase\View\StatementSectionsView
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ViewFactoryTest extends PHPUnit_Framework_TestCase {

	private function newViewFactory(
		EntityIdFormatterFactory $htmlFactory = null,
		EntityIdFormatterFactory $plainFactory = null
	) {
		$templateFactory = new TemplateFactory( new TemplateRegistry( array() ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		return new ViewFactory(
			$htmlFactory ?: $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_HTML ),
			$plainFactory ?: $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_PLAIN ),
			$this->getSnakFormatterFactory(),
			new NullStatementGrouper(),
			new HashSiteStore(),
			new DataTypeFactory( array() ),
			$templateFactory,
			$languageNameLookup,
			$this->getMock( LanguageDirectionalityLookup::class ),
			new BasicNumberLocalizer(),
			array(),
			array(),
			array(),
			$this->getMock( LocalizedTextProvider::class )
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException(
		EntityIdFormatterFactory $htmlFormatterFactory,
		EntityIdFormatterFactory $plainFormatterFactory
	) {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newViewFactory( $htmlFormatterFactory, $plainFormatterFactory );
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

	public function testNewItemView() {
		$factory = $this->newViewFactory();
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$itemView = $factory->newItemView(
			'de',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$editSectionGenerator,
			$this->getMock( EntityTermsView::class )
		);

		$this->assertInstanceOf( ItemView::class, $itemView );
	}

	public function testNewPropertyView() {
		$factory = $this->newViewFactory();
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$propertyView = $factory->newPropertyView(
			'de',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$editSectionGenerator,
			$this->getMock( EntityTermsView::class )
		);

		$this->assertInstanceOf( PropertyView::class, $propertyView );
	}

	public function testNewStatementSectionsView() {
		$statementSectionsView = $this->newViewFactory()->newStatementSectionsView(
			'de',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$this->getMock( EditSectionGenerator::class )
		);

		$this->assertInstanceOf( StatementSectionsView::class, $statementSectionsView );
	}

	/**
	 * @param string $format
	 *
	 * @return EntityIdFormatterFactory
	 */
	private function getEntityIdFormatterFactory( $format ) {
		$entityIdFormatter = $this->getMock( EntityIdFormatter::class );

		$formatterFactory = $this->getMock( EntityIdFormatterFactory::class );

		$formatterFactory->expects( $this->any() )
			->method( 'getOutputFormat' )
			->will( $this->returnValue( $format ) );

		$formatterFactory->expects( $this->any() )
			->method( 'getEntityIdFormatter' )
			->will( $this->returnValue( $entityIdFormatter ) );

		return $formatterFactory;
	}

	/**
	 * @return HtmlSnakFormatterFactory
	 */
	private function getSnakFormatterFactory() {
		$snakFormatter = $this->getMock( SnakFormatter::class );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		$snakFormatterFactory = $this->getMock( HtmlSnakFormatterFactory::class );

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $snakFormatter ) );

		return $snakFormatterFactory;
	}

}
