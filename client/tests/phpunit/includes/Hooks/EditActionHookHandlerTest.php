<?php

namespace Wikibase\Client\Tests\Hooks;

use EditPage;
use Html;
use IContextSource;
use PHPUnit4And6Compat;
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
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class EditActionHookHandlerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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

		$this->assertSame( $expected, $editor->editFormTextAfterTools, $message );
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
			->will( $this->returnCallback( function (
				EntityId $entityId,
				array $classes = [],
				$text = null
			) {
				return Html::rawElement( 'a', [
					'href' => 'https://www.wikidata.org/wiki/' . $entityId,
					'class' => implode( ' ', $classes ),
				], $text ?: $entityId );
			} ) );

		$siteLinkLookup = $this->getMock( SiteLinkLookup::class );

		$siteLinkLookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnValue( $entityId ) );

		$sqlUsageTracker = $this->getMockBuilder( SqlUsageTracker::class )
			->disableOriginalConstructor()
			->getMock();

		$entityUsage = $entityId ? [ new EntityUsage( $entityId, 'S' ) ] : null;
		$sqlUsageTracker->expects( $this->once() )
			->method( 'getUsagesForPage' )
			->will( $this->returnValue( $entityUsage ) );

		$labelDescriptionLookupFactory = $this->getMockBuilder(
			LanguageFallbackLabelDescriptionLookupFactory::class
		)
			->disableOriginalConstructor()
			->getMock();

		$labelDescriptionLookupFactory->expects( $this->any() )
			->method( 'newLabelDescriptionLookup' )
			->will( $this->returnCallback( [ $this, 'newLabelDescriptionLookup' ] ) );

		$idParser = $this->getMock( EntityIdParser::class );

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnCallback( function ( $idSerialization ) {
				return new ItemId( $idSerialization );
			} ) );

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
		$lookup = $this->getMock( LabelDescriptionLookup::class );

		$lookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function ( EntityId $entityId ) {
				switch ( $entityId->getSerialization() ) {
					case 'Q4':
						return new Term( 'en', 'Berlin' );
					default:
						return null;
				}
			} ) );

		return $lookup;
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
