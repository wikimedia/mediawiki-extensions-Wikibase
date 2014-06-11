<?php

namespace Wikibase\Test;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\EntityContent;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ItemContent
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseRepo
 * @group WikibaseContent
 * @group WikibaseItemContent
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author aude
 */
class ItemContentTest extends EntityContentTest {

	public function setUp() {
		parent::setUp();
	}

	/**
	 * @see EntityContentTest::getContentClass
	 */
	protected function getContentClass() {
		return '\Wikibase\ItemContent';
	}

	public function provideEquals() {
		return array(
			array( #0
				array(),
				array(),
				true
			),
			array( #1
				array( 'label' => array() ),
				array( 'description' => array() ),
				true
			),
			array( #2
				array( 'entity' => 'q23' ),
				array(),
				true
			),
			array( #3
				array( 'entity' => 'q23' ),
				array( 'entity' => 'q24' ),
				false
			),
			array( #4
				array( 'label' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'label' => array(
					'en' => 'foo',
				) ),
				false
			),
			array( #5
				array( 'label' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'label' => array(
					'de' => 'bar',
					'en' => 'foo',
				) ),
				true
			),
			array( #6
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO' ),
				) ),
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO', 'xyz' ),
				) ),
				false
			),
		);
	}

	/**
	 * @dataProvider provideEquals
	 */
	public function testEquals( array $a, array $b, $equals ) {
		$itemA = $this->newFromArray( $a );
		$itemB = $this->newFromArray( $b );

		$actual = $itemA->equals( $itemB );
		$this->assertEquals( $equals, $actual );

		$actual = $itemB->equals( $itemA );
		$this->assertEquals( $equals, $actual );
	}

	/**
	 * Tests @see Wikibase\Entity::getTextForSearchIndex
	 *
	 * @dataProvider getTextForSearchIndexProvider
	 *
	 * @param EntityContent $itemContent
	 * @param string $pattern
	 */
	public function testGetTextForSearchIndex( EntityContent $itemContent, $pattern ) {
		$text = $itemContent->getTextForSearchIndex();
		$this->assertRegExp( $pattern . 'm', $text );
	}

	public function getTextForSearchIndexProvider() {
		/** @var ItemContent $itemContent */
		$itemContent = $this->newEmpty();
		$itemContent->getEntity()->setLabel( 'en', "cake" );
		$itemContent->getEntity()->addSiteLink( new SimpleSiteLink( 'dewiki', 'Berlin' ) );

		return array(
			array( $itemContent, '!^cake$!' ),
			array( $itemContent, '!^Berlin$!' )
		);
	}

	public function providePageProperties() {
		$cases = parent::providePageProperties();

		$contentLinkStub = ItemContent::newEmpty();
		$contentLinkStub->getEntity()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$cases['sitelinks'] = array(
			$contentLinkStub,
			array( 'wb-claims' => 0, 'wb-sitelinks' => 1 )
		);

		return $cases;
	}

	public function provideGetEntityStatus() {
		$cases = parent::provideGetEntityStatus();

		$contentLinkStub = ItemContent::newEmpty();
		$contentLinkStub->getEntity()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$cases['linkstub'] = array(
			$contentLinkStub,
			ItemContent::STATUS_LINKSTUB
		);

		$linksAndTerms = $contentLinkStub->copy();
		$linksAndTerms->getEntity()->setLabel( 'en', 'foo' );

		$cases['linkstub with terms'] = array(
			$linksAndTerms,
			ItemContent::STATUS_LINKSTUB
		);

		$cases['statements and links'] = $cases['claims']; // from parent::provideGetEntityStatus();
		$cases['statements and links'][0]->getEntity()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		return $cases;
	}

	public function provideGetEntityPageProperties() {
		$cases = parent::provideGetEntityPageProperties();

		$claim = array( 'm' => array( 'novalue', 11 ), 'q' => array(), 'g' => 'P11x' );

		$cases['claims'] = array(
			array( 'claims' => array( 'P11a' => $claim ) ),
			array(
				'wb-claims' => 1,
			)
		);

		// expect wb-sitelinks => 0 for all inherited cases
		foreach ( $cases as &$case ) {
			$case[1]['wb-sitelinks'] = 0;
		}

		$cases['sitelinks'] = array(
			array( 'links' => array( 'enwiki' => array( 'name' => 'Foo', 'badges' => array() ) ) ),
			array(
				'wb-claims' => 0,
				'wb-sitelinks' => 1,
				'wb-status' => ItemContent::STATUS_LINKSTUB,
			)
		);

		return $cases;
	}

}
