<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use Wikibase\Client\Hooks\EditActionHookHandler;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\Hooks\EditActionHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class EditActionHookHandlerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( callable $expectedFactory, ?EntityId $entityId, string $message ) {
		$context = $this->getContext();
		$expected = $expectedFactory( $context );
		$out = new OutputPage( $context );
		$hookHandler = $this->newHookHandler( $entityId );
		$editor = $this->getEditPage();
		$tabindex = 0; // unused but must be a variable to be passed by reference
		$hookHandler->onEditPage__showStandardInputs_options( $editor, $out, $tabindex );

		$this->assertSame( $expected, $editor->editFormTextAfterTools, $message );
		$this->assertContains( 'wikibase.client.action.edit.collapsibleFooter', $out->getModules() );
	}

	public static function handleProvider() {
		$expectedEditFormText = function ( IContextSource $context, string $link ) {
			$explanation = $context->msg( 'wikibase-pageinfo-entity-usage' )->escaped();
			$header = '<div class="wikibase-entity-usage"><div class="wikibase-entityusage-explanation">';
			$header .= "<p>$explanation\n</p></div>";
			return "$header\n<ul><li>$link: Sitelink</li></ul></div>";
		};

		$cases = [];

		$cases[] = [
			fn ( IContextSource $context ) => $expectedEditFormText(
				$context,
				'<a href="https://www.wikidata.org/wiki/Q4" class="external">Berlin</a>',
			),
			new ItemId( 'Q4' ),
			'item id link',
		];

		$cases[] = [
			fn () => '',
			null,
			'page is not connected to an item',
		];

		$cases[] = [
			fn ( IContextSource $context ) => $expectedEditFormText(
				$context,
				'<a href="https://www.wikidata.org/wiki/Q5" class="external">Q5</a>',
			),
			new ItemId( 'Q5' ),
			'No label for Q5',
		];

		return $cases;
	}

	/**
	 * @param ItemId|null $entityId
	 *
	 * @return EditActionHookHandler
	 */
	private function newHookHandler( ?EntityId $entityId ) {
		$repoLinker = $this->getMockBuilder( RepoLinker::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'buildEntityLink' ] )
			->getMock();

		$repoLinker->method( 'buildEntityLink' )
			->willReturnCallback( function (
				EntityId $entityId,
				array $classes = [],
				$text = null
			) {
				return Html::rawElement( 'a', [
					'href' => 'https://www.wikidata.org/wiki/' . $entityId,
					'class' => $classes,
				], $text ?: $entityId );
			} );

		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->method( 'getItemIdForLink' )
			->willReturn( $entityId );

		$sqlUsageTracker = $this->createMock( SqlUsageTracker::class );
		$entityUsage = $entityId ? [ new EntityUsage( $entityId, 'S' ) ] : [];
		$sqlUsageTracker->expects( $this->once() )
			->method( 'getUsagesForPage' )
			->willReturn( $entityUsage );

		$labelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$labelDescriptionLookupFactory->method( 'newLabelDescriptionLookup' )
			->willReturnCallback( [ $this, 'newLabelDescriptionLookup' ] );

		return new EditActionHookHandler(
			$repoLinker,
			false,
			$sqlUsageTracker,
			$labelDescriptionLookupFactory
		);
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

	public function newLabelDescriptionLookup(): FallbackLabelDescriptionLookup {
		$lookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$lookup->method( 'getLabel' )
			->willReturnCallback( function ( EntityId $entityId ) {
				switch ( $entityId->getSerialization() ) {
					case 'Q4':
						return new Term( 'en', 'Berlin' );
					default:
						return null;
				}
			} );

		return $lookup;
	}

	/**
	 * @return EditPage
	 */
	private function getEditPage() {
		$title = $this->getTitle();

		$editor = $this->createMock( EditPage::class );

		$editor->method( 'getTitle' )
			->willReturn( $title );

		$editor->editFormTextAfterTools = '';

		return $editor;
	}

	/**
	 * @return Title
	 */
	private function getTitle() {
		$title = $this->createMock( Title::class );

		$title->method( 'exists' )
			->willReturn( true );

		$title->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->method( 'getPrefixedText' )
			->willReturn( 'Cat' );

		$title->method( 'getArticleID' )
			->willReturn( 1 );

		return $title;
	}

}
