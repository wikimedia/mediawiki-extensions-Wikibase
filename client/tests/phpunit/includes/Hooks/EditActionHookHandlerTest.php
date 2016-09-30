<?php

namespace Wikibase\Client\Tests\Hooks;

use EditPage;
use Html;
use IContextSource;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\EditActionHookHandler;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\Usage\EntityUsage;

/**
 * @covers Wikibase\Client\Hooks\EditActionHookHandler
 *
 * @group WikibaseClient
 * @group EditActionHookHandler
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class EditActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 * @param string HTML $expected
	 * @param IContextSource $context
	 * @param EntityId|bool $entityId
	 * @param string $message
	 */
	public function testHandle( $expected, IContextSource $context, $entityId, $message ) {
		$hookHandler = $this->newHookHandler( $entityId, $context );
		$editor = $this->getEditPage();
		$hookHandler->handle( $editor );

		$this->assertEquals( $expected, $editor->editFormTextAfterTools, $message );
	}

	public function testNewFromGlobalState() {
		$context = $this->getContext();

		$handler = EditActionHookHandler::newFromGlobalState( $context );
		$this->assertInstanceOf( EditActionHookHandler::class, $handler );

	}

	public function handleProvider() {
		$context = $this->getContext();
		$labeledLink = '<a href="https://www.wikidata.org/wiki/Q4" class="external">Berlin</a>';
		$q5Link = '<a href="https://www.wikidata.org/wiki/Q5" class="external">Q5</a>';
		$explanation = $context->msg( 'wikibase-pageinfo-entity-usage' )->escaped();
		$header = '<div class="wikibase-entity-usage"><div class="wikibase-entityusage-explanation">';
		$header .= "<p>$explanation\n</p></div>";
		$cases = [];

		$cases[] = [
			"$header\n<ul><li>$labeledLink: Sitelink</li></ul></div>",
			$context,
			new ItemId( 'Q4' ),
			'item id link'
		];

		$cases[] = [
			'',
			$context,
			false,
			'page is not connected to an item'
		];

		$cases[] = [
			"$header\n<ul><li>$q5Link: Sitelink</li></ul></div>",
			$context,
			new ItemId( 'Q5' ),
			'No label for Q5'
		];

		return $cases;
	}

	/**
	 * @param ItemId|bool $entityId
	 * @param IContextSource $context
	 *
	 * @return EditActionHookHandler
	 */
	private function newHookHandler( $entityId, IContextSource $context ) {

		$repoLinker = $this->getMockBuilder( RepoLinker::class )
			->disableOriginalConstructor()
			->setMethods( [ 'buildEntityLink' ] )
			->getMock();

		$repoLinker->expects( $this->any() )
			->method( 'buildEntityLink' )
			->will( $this->returnCallback( [ $this, 'buildEntityLink' ] ) );

		$siteLinkLookup = $this->getMockBuilder( SiteLinkLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnValue( $entityId ) );

		$sqlUsageTracker = $this->getMockBuilder( SqlUsageTracker::class )
			->disableOriginalConstructor()
			->getMock();

		if ( $entityId ) {
			$entityUsage = [ new EntityUsage( $entityId, 'S' ) ];
			$sqlUsageTracker->expects( $this->any() )
				->method( 'getUsagesForPage' )
				->will( $this->returnValue( $entityUsage ) );
		}

		$labelDescriptionLookupFactory = $this->getMockBuilder(
			LanguageFallbackLabelDescriptionLookupFactory::class
		)
			->disableOriginalConstructor()
			->getMock();

		$labelDescriptionLookupFactory->expects( $this->any() )
			->method( 'newLabelDescriptionLookup' )
			->will( $this->returnCallback( [ $this, 'newLabelDescriptionLookup' ] ) );

		$idParser = $this->getMockBuilder( EntityIdParser::class )
			->disableOriginalConstructor()
			->getMock();

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnCallback( [ $this, 'parse' ] ) );

		$hookHandler = new EditActionHookHandler(
			$repoLinker,
			$sqlUsageTracker,
			$labelDescriptionLookupFactory,
			$idParser,
			$context
		);

		return $hookHandler;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->getTitle();

		$context = new RequestContext();
		$context->setTitle( $title );

		$context->setLanguage( 'en' );

		return $context;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	public function newLabelDescriptionLookup() {
		$lookup = $this->getMockBuilder( LabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( [ $this, 'getLabel' ] ) );

		return $lookup;
	}

	/**
	 * @param EntityId $entity
	 *
	 * @return Term|null
	 */
	public function getLabel( EntityId $entity ) {
		$labelMap = [ 'Q4' => 'Berlin' ];
		$idSerialization = $entity->getSerialization();
		if ( !isset( $labelMap[$idSerialization] ) ) {
			return null;
		}
		$term = new Term( 'en', $labelMap[$idSerialization] );
		return $term;
	}

	/**
	 * @param string $entity
	 *
	 * @return ItemId
	 */
	public function parse( $entity ) {
		// TODO: Let properties be tested too
		return new ItemId( $entity );
	}

	/**
	 * @param string $entityId
	 * @param string[] $classes
	 * @param string|null $text
	 *
	 * @return string HTML
	 */
	public function buildEntityLink( $entityId, array $classes, $text = null ) {
		if ( $text === null ) {
			$text = $entityId;
		}

		$attr = [
			'href' => 'https://www.wikidata.org/wiki/' . $entityId,
			'class' => implode( ' ', $classes )
		];

		return Html::rawElement( 'a', $attr, $text );
	}

	/**
	 * @return EditPage
	 */
	private function getEditPage() {
		$title = $this->getTitle();

		$editor = $this->getMockBuilder( EditPage::class )
			->disableOriginalConstructor()
			->getMock();

		$editor->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$editor->editFormTextAfterTools = '';

		return $editor;
	}

	/**
	 * @return Title
	 */
	private function getTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Cat' ) );

		return $title;
	}

}
