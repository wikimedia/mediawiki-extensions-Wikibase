<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use Html;
use IContextSource;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\InfoActionHookHandler;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\Hooks\InfoActionHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandlerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideTestOnInfoActionData
	 */
	public function testOnInfoAction(
		array $expected,
		IContextSource $context,
		array $pageInfo,
		$enabled,
		?ItemId $itemId,
		$localDescription,
		$centralDescription,
		$message
	) {
		$hookHandler = $this->newHookHandler( $enabled, $itemId, $localDescription, $centralDescription );
		$hookHandler->onInfoAction( $context, $pageInfo );

		$this->assertEquals( $expected, $pageInfo, $message );
	}

	public function provideTestOnInfoActionData() {
		$context = $this->getContext();
		$labeledLink = '<a href="https://www.wikidata.org/wiki/Q4" class="external">Berlin</a>';
		$unLabeledLink = '<a href="https://www.wikidata.org/wiki/Q4" class="external">Q4</a>';
		$q5Link = '<a href="https://www.wikidata.org/wiki/Q5" class="external">Q5</a>';

		return [
			[
				[
					'header-basic' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
							$unLabeledLink,
						],
					],
					'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$labeledLink</li><ul><li>Sitelink</li></ul></ul>",
						],
					],
				],
				$context,
				[ 'header-basic' => [] ],
				true,
				new ItemId( 'Q4' ),
				null,
				null,
				'item id link',
			],
			[
				[ 'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$labeledLink</li><ul><li>Sitelink</li></ul></ul>",
						],
					],
				],
				$context,
				[ 'header-properties' => [] ],
				false,
				new ItemId( 'Q4' ),
				null,
				null,
				'namespace does not have wikibase enabled',
			],
			[
				[
					'header-basic' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
							$context->msg( 'wikibase-pageinfo-entity-id-none' )->escaped(),
						],
					],
				],
				$context,
				[ 'header-basic' => [] ],
				true,
				null,
				null,
				null,
				'page is not connected to an item',
			],
			[
				[ 'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$q5Link</li><ul><li>Sitelink</li></ul></ul>",
						],
					],
				],
				$context,
				[ 'header-properties' => [] ],
				false,
				new ItemId( 'Q5' ),
				null,
				null,
				'No label for Q5',
			],
			[
				[
					'header-basic' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
							$unLabeledLink,
						],
						[
							$context->msg( 'wikibase-pageinfo-description-local' )->escaped(),
							'this is the local description',
						],
						[
							$context->msg( 'wikibase-pageinfo-description-central' )->escaped(),
							'this is the central description',
						],
					],
					'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$labeledLink</li><ul><li>Sitelink</li></ul></ul>",
						],
					],
				],
				$context,
				[ 'header-basic' => [] ],
				true,
				new ItemId( 'Q4' ),
				'this is the local description',
				'this is the central description',
				'description',
			],
		];
	}

	/**
	 * @param bool $enabled
	 * @param ItemId|null $itemId
	 * @param string $localDescription
	 * @param string $centralDescription
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler(
		$enabled,
		?ItemId $itemId,
		$localDescription,
		$centralDescription
	) {
		$namespaceChecker = $this->createMock( NamespaceChecker::class );

		$namespaceChecker->method( 'isWikibaseEnabled' )
			->willReturn( $enabled );

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
			->willReturn( $itemId );

		$sqlUsageTracker = $this->createMock( SqlUsageTracker::class );
		if ( $itemId ) {
			$entityUsage = [ new EntityUsage( $itemId, 'S' ) ];
			$sqlUsageTracker->method( 'getUsagesForPage' )
				->willReturn( $entityUsage );
		}

		$labelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$labelDescriptionLookupFactory->method( 'newLabelDescriptionLookup' )
			->willReturnCallback( [ $this, 'newLabelDescriptionLookup' ] );

		$descriptionLookup = $this->createMock( DescriptionLookup::class );

		$descriptionLookup->expects( $this->atLeast( 2 ) )
			->method( 'getDescription' )
			->withConsecutive(
				[ $this->anything(), DescriptionLookup::SOURCE_LOCAL ],
				[ $this->anything(), DescriptionLookup::SOURCE_CENTRAL ]
			)
			->willReturnOnConsecutiveCalls(
				$localDescription,
				$centralDescription
			);

		return new InfoActionHookHandler(
			$namespaceChecker,
			$repoLinker,
			$siteLinkLookup,
			'enwiki',
			$sqlUsageTracker,
			$labelDescriptionLookupFactory,
			$descriptionLookup
		);
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->createMock( Title::class );

		$title->method( 'exists' )
			->willReturn( true );

		$title->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->method( 'getPrefixedText' )
			->willReturn( 'Cat' );

		$title->method( 'getArticleID' )
			->willReturn( 1 );

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

}
