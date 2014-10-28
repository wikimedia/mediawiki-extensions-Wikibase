<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Repo\Specials\SpecialItemDisambiguation;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemDisambiguation
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SpecialItemDisambiguationTest extends SpecialPageTestBase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$mock = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					return Title::makeTitle( NS_MAIN, $id->getSerialization() );
				}
			) );

		return $mock;
	}

	/**
	 * @return TermIndex
	 */
	private function getTermIndex() {
		$terms = array();

		$terms[] = new \Wikibase\Term( array(
			'entityId' => 1,
			'entityType' => 'item',
			'termText' => 'one',
			'termLanguage' => 'en',
			'termType' => 'label'
		) );

		$terms[] = new \Wikibase\Term( array(
			'entityId' => 1,
			'entityType' => 'item',
			'termText' => 'eins',
			'termLanguage' => 'de',
			'termType' => 'label'
		) );

		$terms[] = new \Wikibase\Term( array(
			'entityId' => 1,
			'entityType' => 'item',
			'termText' => 'number',
			'termLanguage' => 'en',
			'termType' => 'description'
		) );

		$terms[] = new \Wikibase\Term( array(
			'entityId' => 1,
			'entityType' => 'item',
			'termText' => 'Zahl',
			'termLanguage' => 'de',
			'termType' => 'description'
		) );

		$terms[] = new \Wikibase\Term( array(
			'entityId' => 11,
			'entityType' => 'item',
			'termText' => 'oneone',
			'termLanguage' => 'en',
			'termType' => 'label'
		) );

		$terms[] = new \Wikibase\Term( array(
			'entityId' => 11,
			'entityType' => 'item',
			'termText' => 'einseins',
			'termLanguage' => 'de',
			'termType' => 'label'
		) );

		return new MockTermIndex( $terms );
	}

	private function getMockEntityLookup() {
		$repo = new MockRepository();

		$one = Item::newEmpty();
		$one->setId( new ItemId( 'Q1' ) );
		$one->setLabel( 'en', 'one' );
		$one->setLabel( 'de', 'eins' );
		$one->setDescription( 'en', 'number' );
		$one->setDescription( 'de', 'Zahl' );

		$repo->putEntity( $one );

		$oneone = Item::newEmpty();
		$oneone->setId( new ItemId( 'Q11' ) );
		$oneone->setLabel( 'en', 'oneone' );
		$oneone->setLabel( 'de', 'einseins' );

		$repo->putEntity( $oneone );

		return $repo;
	}

	protected function newSpecialPage() {
		$page = new SpecialItemDisambiguation();

		$page->initServices(
			$this->getTermIndex(),
			$this->getEntityLookup(),
			$this->getEntityTitleLookup()
		);

		return $page;
	}

	public function requestProvider() {
		$cases = array();
		$matchers = array();

		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itemdisambiguation-languagename',
				'class' => 'wb-input-text',
				'name' => 'language',
			) );
		$matchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'labelname',
				'class' => 'wb-input-text',
				'name' => 'label',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );

		$cases['empty'] = array( '', array(), null, $matchers );

		// en/one
		$matchers['language']['attributes']['value'] = 'en';
		$matchers['label']['attributes']['value'] = 'one';

		$matchers['matches'] = array(
			'tag' => 'ul',
			'children' => array( 'count' => 2 ),
			'attributes' => array( 'class' => 'wikibase-disambiguation' ),
		);

		$matchers['one'] = array(
			'tag' => 'a',
			'parent' => array( 'tag' => 'li' ),
			'content' => 'one',
			'attributes' => array( 'title' => 'Q1' ),
		);

		$matchers['one/desc'] = array(
			'tag' => 'span',
			'content' => 'number',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);

		$matchers['oneone'] = array(
			'tag' => 'a',
			//'parent' => array( 'tag' => 'li' ), // PHPUnit's assertTag doesnt like this here
			'content' => 'oneone',
			'attributes' => array( 'title' => 'Q11' ),
		);

		$matchers['oneone/desc'] = array(
			'tag' => 'span',
			//'content' => 'Q11',
			'attributes' => array( 'class' => 'wb-itemlink-description' ),
		);

		$cases['en/one'] = array( 'en/one', array(), 'en', $matchers );

		// de/eins
		$matchers['language']['attributes']['value'] = 'de';
		$matchers['label']['attributes']['value'] = 'eins';

		$matchers['one'] = array(
			'tag' => 'a',
			'parent' => array( 'tag' => 'li' ),
			'content' => 'one',
			'attributes' => array( 'title' => 'Q1' ),
		);

		$matchers['oneone'] = array(
			'tag' => 'a',
			//'parent' => array( 'tag' => 'li' ), // PHPUnit's assertTag doesnt like this here
			'content' => 'oneone',
			'attributes' => array( 'title' => 'Q11' ),
		);

		$matchers['one/de'] = array(
			'tag' => 'span',
			'parent' => array( 'tag' => 'li' ),
			'content' => 'eins',
			'attributes' => array( 'lang' => 'de' ),
		);

		$matchers['oneone/de'] = array(
			'tag' => 'span',
			//'parent' => array( 'tag' => 'li' ), // PHPUnit's assertTag doesnt like this here
			'content' => 'einseins',
			'attributes' => array( 'lang' => 'de' ),
		);

		$cases['de/eins'] = array( 'de/eins', array(), 'en', $matchers );

		// en/unknown
		$matchers['language']['attributes']['value'] = 'en';
		$matchers['label']['attributes']['value'] = 'unknown';

		unset( $matchers['matches'] );
		unset( $matchers['one'] );
		unset( $matchers['one/desc'] );
		unset( $matchers['oneone'] );
		unset( $matchers['one/de'] );
		unset( $matchers['oneone/de'] );
		unset( $matchers['oneone/desc'] );

		$matchers['sorry'] = array(
			'tag' => 'p',
			'content' => 'regexp:/^Sorry.*found/'
		);

		$cases['en/unknown'] = array( 'en/unknown', array(), 'en', $matchers );

		// invalid/unknown
		$matchers['language']['attributes']['value'] = 'invalid';
		$matchers['label']['attributes']['value'] = 'unknown';
		$matchers['sorry']['content'] = 'regexp:/^Sorry.*language/';

		$cases['invalid/unknown'] = array( 'invalid/unknown', array(), 'en', $matchers );

		return $cases;
	}

	/**
	 * @dataProvider requestProvider
	 *
	 * @param $sub
	 * @param $request
	 * @param $userLanguage
	 * @param $matchers
	 */
	public function testExecute( $sub, $request, $userLanguage, $matchers ) {
		$request = new \FauxRequest( $request );

		list( $output, ) = $this->executeSpecialPage( $sub, $request, $userLanguage );
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
