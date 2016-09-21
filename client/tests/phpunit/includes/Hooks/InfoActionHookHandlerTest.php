<?php

namespace Wikibase\Client\Tests\Hooks;

use Html;
use IContextSource;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\InfoActionHookHandler;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\NamespaceChecker;

/**
 * @covers Wikibase\Client\Hooks\InfoActionHookHandler
 *
 * @group WikibaseClient
 * @group InfoActionHookHandler
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( $expected, $context, $pageInfo, $enabled, $entityId, $message ) {
		$hookHandler = $this->newHookHandler( $enabled, $entityId );
		$pageInfo = $hookHandler->handle( $context, $pageInfo );

		$this->assertEquals( $expected, $pageInfo, $message );
	}

	public function handleProvider() {
		$context = $this->getContext();
		$labeledLink = '<a href="https://www.wikidata.org/wiki/Q4" classes="external">Berlin</a>';
		$unLabeledLink = '<a href="https://www.wikidata.org/wiki/Q4" classes="external">Q4</a>';
		$q5Link = '<a href="https://www.wikidata.org/wiki/Q5" classes="external">Q5</a>';
		$cases = [];

		$cases[] = [
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
			$context, [ 'header-basic' => [] ], true, new ItemId( 'Q4' ),
			'item id link'
		];

		$cases[] = [
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
		];

		$cases[] = [
			[
				'header-basic' => [
					[
						$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
						$context->msg( 'wikibase-pageinfo-entity-id-none' )->escaped()
					]
				]
			],
			$context, [ 'header-basic' => [] ], true, false,
			'page is not connected to an item'
		];

		$cases[] = [
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
		];

		return $cases;
	}

	/**
	 * @param bool $enabled
	 * @param ItemId $entityId
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, $entityId ) {
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
			$entityUsage = array( new EntityUsage( $entityId, 'S' ) );
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
	 * @return Term
	 */
	public function getLabel( $entity ) {
		$labelMap = [ 'Q4' => 'Berlin' ];
		$entityId = $entity->getSerialization();
		if ( !isset( $labelMap[$entityId] ) ) {
			return null;
		}
		$term = new Term( 'en', $labelMap[$entityId] );
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
	 * @param string $text
	 *
	 * @return string HTML
	 */
	public function buildEntityLink( $entityId, array $classes, $text = null ) {
		if ( $text === null ) {
			$text = $entityId;
		}

		$attr = [
			'href' => 'https://www.wikidata.org/wiki/' . $entityId,
			'classes' => implode( ' ', $classes )
		];

		return Html::rawElement( 'a', $attr, $text );
	}

}
