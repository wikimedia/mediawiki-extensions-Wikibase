<?php

namespace Wikibase\Test;
use Wikibase\ItemContent as ItemContent;
use Wikibase\Utils;
use Wikibase\ItemView as ItemView;

/**
 * Test WikibaseItemView.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
 *
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 * 
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class ItemViewTest extends \MediaWikiTestCase {

	protected static $num = -1;

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			Utils::insertSitesForTests();
			$hasSites = true;
		}
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetHTML
	 */
	public function testGetHTML( $itemData, $expected) {
		self::$num++;

		$itemContent = $itemData === false ? ItemContent::newEmpty() : ItemContent::newFromArray( $itemData );

		$itemContent->getItem()->setLabel( 'de', 'Stockholm' );

		$this->assertTrue(
			!is_null( $itemContent ) && $itemContent !== false,
			"Could not find an item"
		);

		$view = new ItemView( );

		$this->assertTrue(
			!is_null( $view ) && $view !== false,
			"Could not find a view"
		);

		$html = $view->getHTML( $itemContent );

		if ( is_string( $expected ) ) {
			$this->assertRegExp(
				$expected,
				$html,
				"Could not find the marker '{$expected}'"
			);
		}
		else {
			foreach ( $expected as $that ) {
				$this->assertRegExp(
					$that,
					$html,
					"Could not find the marker '{$that}'"
				);
			}
		}

	}

	// FIXME: this stuff is broken, AGAIN...
	// Should use proper abstraction and not create items from arrays
	public function providerGetHTML() {
		return array(
			array(
				false,
				'/"wb-sitelinks-empty"/'
			),
			array(
				array(
					'links'=> array(
						'enwiki' => 'Oslo',
					)
				),
				array(
					'/"wb-sitelinks"/',
					'/"wb-sitelinks-en uneven"/',
				//	'/<a>\s*Oslo\s*<\/a>/'
				)
			),
			array(
				array(
					'links'=> array(
						'dewiki' => 'Stockholm',
						'enwiki' => 'Oslo',
					)
				),
				array(
					'/"wb-sitelinks"/',
					'/"wb-sitelinks-de uneven"/',
					'/"wb-sitelinks-en even"/',
				//	'/<a>\s*Oslo\s*<\/a>/',
				//	'/<a>\s*Stockholm\s*<\/a>/'
				)
			),
			array(
				array(
					'description'=> array(
						'en' => 'Capitol of Norway'
					),
					'links'=> array(
						'enwiki' => 'Oslo',
					),
				),
				array(
					'/"wb-sitelinks"/',
					'/<span class="wb-property-container-value">\s*Capitol of Norway\s*<\/span>/',
				//	'/<a>\s*Oslo\s*<\/a>/'
				)
			),
		);
	}

}
