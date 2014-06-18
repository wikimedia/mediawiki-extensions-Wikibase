<?php

namespace Wikibase\Test;

use Language;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\EntityViewPlaceholderExpander;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\StorageException;

/**
 * @covers Wikibase\EntityViewPlaceholderExpander
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpanderTest extends \MediaWikiTestCase {

	/**
	 * @param User $user
	 * @param EntityLookup $entityLookup
	 * @param ItemId $itemId
	 */
	private function newExpander( User $user, EntityRevisionLookup $entityRevisionLookup, ItemId $itemId ) {
		$title = new Title( 'EntityViewPlaceholderExpanderTest-DummyTitleForLocalUrls' );

		$language = Language::factory( 'en' );

		$idParser = $this->getMockBuilder( 'Wikibase\DataModel\Entity\EntityIdParser' )
			->disableOriginalConstructor()
			->getMock();

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $itemId ) );

		$userLanguages = $this->getMockBuilder( 'Wikibase\UserLanguageLookup' )
			->disableOriginalConstructor()
			->getMock();

		$userLanguages->expects( $this->any() )
			->method( 'getExtraUserLanguages' )
			->will( $this->returnValue( array( 'de', 'en', 'ru' ) ) );

		return new EntityViewPlaceholderExpander(
			$title,
			$user,
			$language,
			$idParser,
			$entityRevisionLookup,
			$userLanguages
		);
	}

	/**
	 * @param Item $item
	 * @param int $revId
	 *
	 * @return EntityRevisionLookup
	 */
	private function getEntityRevisionLookup( Item $item = null, $revId = 5 ) {
		$revision = ( $item === null ) ? null : new EntityRevision( $item, $revId );

		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnValue( $revision ) );

		return $entityLookup;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function getExceptionThrowingEntityRevisionLookup() {
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( function() {
				throw new StorageException( 'Entity not found' );
			} )
		);

		return $entityLookup;
	}

	private function getItem() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q23' ) );

		$item->setLabel( 'en', 'Moskow' );
		$item->setLabel( 'de', 'Moskau' );

		$item->setDescription( 'de', 'Hauptstadt Russlands' );

		return $item;
	}

	private function newUser( $isAnon ) {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
		$user->expects( $this->any() )
			->method( 'isAnon' )
			->will( $this->returnValue( $isAnon ) );
		$user->setName( 'EntityViewPlaceholderExpanderTest-DummyUser' );

		return $user;
	}

	public function testGetHtmlForPlaceholder() {
		$item = $this->getItem();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );
		$expander = $this->newExpander( $this->newUser( false ), $entityRevisionLookup, $item->getId() );

		$html = $expander->getHtmlForPlaceholder( 'termbox-toc' );
		$this->assertInternalType( 'string', $html );

		$html = $expander->getHtmlForPlaceholder( 'termbox', 'Q23' );
		$this->assertInternalType( 'string', $html );
	}

	public function testRenderTermBoxTocEntry() {
		$item = $this->getItem();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );
		$expander = $this->newExpander( $this->newUser( false ), $entityRevisionLookup, $item->getId() );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBoxTocEntry( new ItemId( 'Q23' ) );
		$this->assertNotNull( $html );
		$this->assertRegExp( '/#wb-terms/', $html );
	}

	public function renderTermBox() {
		$item = $this->getItem();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );
		$expander = $this->newExpander( $this->newUser( false ), $entityRevisionLookup, $item->getId() );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBox( new ItemId( 'Q23' ), 0 );

		$this->assertRegExp( '/Moskau/', $html );
		$this->assertRegExp( '/Hauptstadt/', $html );

		$this->assertNotRegExp( '/Moskow/', $html );
		$this->assertNotRegExp( '/Capitol/', $html );
	}

	public function testRenderTermBoxForDeleteRevision() {
		$item = $this->getItem();
		$itemId = $item->getId();
		$entityRevisionLookup = $this->getExceptionThrowingEntityRevisionLookup();

		$expander = $this->newExpander( $this->newUser( false ), $entityRevisionLookup, $itemId );

		$html = $expander->renderTermBox( $itemId, 1 );
		$this->assertEquals( '', $html );
	}

	public function testGetExtraUserLanguages() {
		$item = $this->getItem();
		$itemId = $item->getId();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );

		$expander = $this->newExpander( $this->newUser( true ), $entityRevisionLookup, $itemId );
		$this->assertArrayEquals( array(), $expander->getExtraUserLanguages() );

		$expander = $this->newExpander( $this->newUser( false ), $entityRevisionLookup, $itemId );
		$this->assertArrayEquals( array( 'de', 'en', 'ru' ), $expander->getExtraUserLanguages() );
	}

}
