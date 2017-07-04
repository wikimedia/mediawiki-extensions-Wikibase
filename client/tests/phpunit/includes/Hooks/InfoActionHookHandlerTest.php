<?php

namespace Wikibase\Client\Tests\Hooks;

use Html;
use IContextSource;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\InfoActionHookHandler;
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
use Wikibase\Client\NamespaceChecker;

/**
 * @covers Wikibase\Client\Hooks\InfoActionHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle(
		array $expected,
		IContextSource $context,
		array $pageInfo,
		$enabled,
		ItemId $itemId = null,
		$message
	) {
		$hookHandler = $this->newHookHandler( $enabled, $itemId );
		$pageInfo = $hookHandler->handle( $context, $pageInfo );

		$this->assertEquals( $expected, $pageInfo, $message );
	}

	public function handleProvider() {
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
							$unLabeledLink
						],
					],
					'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$labeledLink</li><ul><li>Sitelink</li></ul></ul>",
						],
					]
				],
				$context,
				[ 'header-basic' => [] ],
				true,
				new ItemId( 'Q4' ),
				'item id link'
			],
			[
				[ 'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$labeledLink</li><ul><li>Sitelink</li></ul></ul>",
						],
					]
				],
				$context,
				[ 'header-properties' => [] ],
				false,
				new ItemId( 'Q4' ),
				'namespace does not have wikibase enabled'
			],
			[
				[
					'header-basic' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
							$context->msg( 'wikibase-pageinfo-entity-id-none' )->escaped()
						]
					]
				],
				$context,
				[ 'header-basic' => [] ],
				true,
				null,
				'page is not connected to an item'
			],
			[
				[ 'header-properties' => [
						[
							$context->msg( 'wikibase-pageinfo-entity-usage' )->escaped(),
							"<ul><li>$q5Link</li><ul><li>Sitelink</li></ul></ul>",
						],
					]
				],
				$context,
				[ 'header-properties' => [] ],
				false,
				new ItemId( 'Q5' ),
				'No label for Q5'
			]
		];
	}

	/**
	 * @param bool $enabled
	 * @param ItemId|null $itemId
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, ItemId $itemId = null ) {
		$namespaceChecker = $this->getMockBuilder( NamespaceChecker::class )
			->disableOriginalConstructor()
			->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( $enabled ) );

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
			->will( $this->returnValue( $itemId ) );

		$sqlUsageTracker = $this->getMockBuilder( SqlUsageTracker::class )
			->disableOriginalConstructor()
			->getMock();

		if ( $itemId ) {
			$entityUsage = [ new EntityUsage( $itemId, 'S' ) ];
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

		$idParser = $this->getMock( EntityIdParser::class );

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnCallback( function ( $idSerialization ) {
				return new ItemId( $idSerialization );
			} ) );

		$hookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			$repoLinker,
			$siteLinkLookup,
			'enwiki',
			$sqlUsageTracker,
			$labelDescriptionLookupFactory,
			$idParser
		);

		return $hookHandler;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
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

}
