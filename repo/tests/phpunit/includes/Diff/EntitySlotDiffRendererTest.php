<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Diff;

use Content;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use MediaWiki\Title\Title;
use OutputPage;
use RequestContext;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\Diff\EntitySlotDiffRenderer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Diff\EntitySlotDiffRenderer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySlotDiffRendererTest extends \MediaWikiIntegrationTestCase {
	private function newFactory( array $instantiators = [] ): EntityDiffVisualizerFactory {
		return new EntityDiffVisualizerFactory(
			$instantiators,
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$this->createMock( SiteLookup::class ),
			WikibaseRepo::getEntityIdHTMLLinkFormatterFactory(),
			WikibaseRepo::getSnakFormatterFactory()
		);
	}

	private function newDiffRenderer(): EntitySlotDiffRenderer {
		$context = new RequestContext;
		$context->setLanguage( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );
		$visualizer = $this->newFactory()->newEntityDiffVisualizer( null, $context );
		return new EntitySlotDiffRenderer( $visualizer, 'en' );
	}

	/**
	 * @dataProvider provideEmptyDiffs
	 */
	public function testEmptyDiffs( Content $oldContent, Content $newContent ) {
		$html = $this->newDiffRenderer()->getDiff( $oldContent, $newContent );
		$this->assertSame( '', $html );
	}

	public static function provideEmptyDiffs() {
		$item = new Item( new ItemId( 'Q1' ) );
		$emptyContent1 = ItemContent::newFromItem( clone $item );
		$emptyContent2 = ItemContent::newFromItem( clone $item );

		$item->setLabel( 'en', 'Not empty any more' );
		$itemContent1 = ItemContent::newFromItem( clone $item );
		$itemContent2 = ItemContent::newFromItem( clone $item );

		return [
			'same object' => [ $itemContent1, $itemContent1 ],
			'empty objects' => [ $emptyContent1, $emptyContent2 ],
			'two non-empty equal objects' => [ $itemContent1, $itemContent2 ],
		];
	}

	/**
	 * @dataProvider provideGetDiff
	 * @param Item|EntityRedirect $item1
	 * @param Item|EntityRedirect $item2
	 * @param array $matchers
	 */
	public function testGetDiff( $item1, $item2, array $matchers ) {
		$itemContent = $this->makeContent( $item1 );
		$itemContent2 = $this->makeContent( $item2 );
		$diffRenderer = $this->newDiffRenderer();
		$html = $diffRenderer->getDiff( $itemContent, $itemContent2 );

		$this->assertIsString( $html );
		foreach ( $matchers as $name => $matcher ) {
			$this->assertStringContainsString( $matcher, $html, $name );
		}
	}

	/**
	 * @param Item|EntityRedirect $item
	 * @return ItemContent
	 */
	private function makeContent( $item ) {
		if ( $item instanceof EntityRedirect ) {
			return ItemContent::newFromRedirect(
				$item,
				$this->createMock( Title::class )
			);
		} else {
			return ItemContent::newFromItem( $item );
		}
	}

	public static function provideGetDiff() {
		$emptyItem = new Item( new ItemId( 'Q1' ) );

		$item = new Item( new ItemId( 'Q11' ) );
		$item->setDescription( 'en', 'ohi there' );
		$item->setLabel( 'de', 'o_O' );
		$item->setAliases( 'nl', [ 'foo', 'bar' ] );

		$item2 = new Item( new ItemId( 'Q12' ) );
		$item2->setLabel( 'de', 'o_O' );
		$item2->setLabel( 'en', 'O_o' );
		$item2->setAliases( 'nl', [ 'daaaah' ] );

		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q21' ) );
		$redirect2 = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q22' ) );

		$insTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <ins>foo</ins>' => '>foo</ins>',
			'has <td>aliases / nl / 0</td>' => '>aliases / nl / 0</td>',
			'has <ins>bar</ins>' => '>bar</ins>',
			'has <td>description / en</td>' => '>description / en</td>',
			'has <ins>ohi there</ins>' => '>ohi there</ins>',
		];

		$delTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <del>foo</del>' => '>foo</del>',
			'has <td>aliases / nl / 0</td>' => '>aliases / nl / 0</td>',
			'has <del>bar</del>' => '>bar</del>',
			'has <td>description / en</td>' => '>description / en</td>',
			'has <del>ohi there</del>' => '>ohi there</del>',
		];

		$changeTags = [
			'has <td>label / en</td>' => '>label / en</td>',
			'has <ins>O_o</ins>' => '>O_o</ins>',
			'has <td>aliases / nl / 0</td>' => '>aliases / nl / 0</td>',
			'has <ins>daaaah</ins>' => '>daaaah</ins>',
			'has <td>aliases / nl / 1</td>' => '>aliases / nl / 1</td>',
			'has <del>foo</del>' => '>foo</del>',
			'has <td>aliases / nl / 2</td>' => '>aliases / nl / 2</td>',
			'has <del>bar</del>' => '>bar</del>',
			'has <td>description / en</td>' => '>description / en</td>',
			'has <del>ohi there</del>' => '>ohi there</del>',
		];

		$fromRedirTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <ins>foo</ins>' => '>foo</ins>',

			'has <td>redirect</td>' => '>redirect</td>',
			'has <del>Q21</del>' => '>Q21</del>',
		];

		$toRedirTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <del>foo</del>' => '>foo</del>',

			'has <td>redirect</td>' => '>redirect</td>',
			'has <ins>Q21</ins>' => '>Q21</ins>',
		];

		$changeRedirTags = [
			'has <td>redirect</td>' => '>redirect</td>',
			'has <del>Q21</del>' => '>Q21</del>',
			'has <ins>Q22</del>' => '>Q22</ins>',
		];

		return [
			'from empty' => [ $emptyItem, $item, $insTags ],
			'to empty' => [ $item, $emptyItem, $delTags ],
			'changed' => [ $item, $item2, $changeTags ],
			'to redirect' => [ $item, $redirect, $toRedirTags ],
			'from redirect' => [ $redirect, $item, $fromRedirTags ],
			'redirect changed' => [ $redirect, $redirect2, $changeRedirTags ],
		];
	}

	public function testAddModules() {
		$output = new class extends OutputPage {
			private $testStyles;

			public function __construct() {
			}

			public function addModuleStyles( $modules ) {
				$this->testStyles = $modules;
			}

			public function getTestModuleStyles() {
				return $this->testStyles;
			}
		};

		$diffRenderer = $this->newDiffRenderer();
		$diffRenderer->addModules( $output );
		$this->assertContains( 'wikibase.alltargets', $output->getTestModuleStyles() );
	}

	public function testGetExtraCacheKeys() {
		$result = $this->newDiffRenderer()->getExtraCacheKeys();
		$this->assertSame( [ 'lang-en' ], $result );
	}
}
