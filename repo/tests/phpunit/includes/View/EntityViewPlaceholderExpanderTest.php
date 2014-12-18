<?php

namespace Wikibase\Test;

use Language;
use MediaWikiTestCase;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\View\EntityViewPlaceholderExpander;
use Wikibase\Template\TemplateFactory;
use Wikibase\Template\TemplateRegistry;

/**
 * @covers Wikibase\Repo\View\EntityViewPlaceholderExpander
 *
 * @uses Wikibase\Repo\View\EntityTermsView
 * @uses Wikibase\Template\Template
 * @uses Wikibase\Template\TemplateFactory
 * @uses Wikibase\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpanderTest extends MediaWikiTestCase {

	/**
	 * @param User $user
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param ItemId $itemId
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function newExpander( User $user, EntityRevisionLookup $entityRevisionLookup, ItemId $itemId ) {
		$title = $this->getMockBuilder( 'Title')
			->disableOriginalConstructor()
			->getMock();

		$language = Language::factory( 'en' );

		$idParser = $this->getMockBuilder( 'Wikibase\DataModel\Entity\EntityIdParser' )
			->disableOriginalConstructor()
			->getMock();

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $itemId ) );

		$userLanguages = $this->getMock( 'Wikibase\Lib\UserLanguageLookup' );

		$userLanguages->expects( $this->any() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( array( 'de', 'en', 'ru' ) ) );

		return new EntityViewPlaceholderExpander(
			new TemplateFactory( TemplateRegistry::getDefaultInstance() ),
			$title,
			$user,
			$language,
			$idParser,
			$entityRevisionLookup,
			$userLanguages,
			new WikibaseContentLanguages(),
			new LanguageNameLookup()
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
		$item = new Item( new ItemId( 'Q23' ) );

		$item->setLabel( 'en', 'Moskow' );
		$item->setLabel( 'de', 'Moskau' );

		$item->setDescription( 'de', 'Hauptstadt Russlands' );

		return $item;
	}

	/**
	 * @param bool $isAnon
	 *
	 * @return User
	 */
	private function newUser( $isAnon = false ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();
		$user->expects( $this->any() )
			->method( 'isAnon' )
			->will( $this->returnValue( $isAnon ) );

		/** @var User $user */
		$user->setName( 'EntityViewPlaceholderExpanderTest-DummyUser' );

		return $user;
	}

	public function testGetHtmlForPlaceholder() {
		$item = $this->getItem();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );
		$expander = $this->newExpander( $this->newUser(), $entityRevisionLookup, $item->getId() );

		$html = $expander->getHtmlForPlaceholder( 'termbox', 'Q23' );
		$this->assertInternalType( 'string', $html );
	}

	public function testRenderTermBox() {
		$item = $this->getItem();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );
		$expander = $this->newExpander( $this->newUser(), $entityRevisionLookup, $item->getId() );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBox( new ItemId( 'Q23' ), 0 );

		$this->assertRegExp( '/Moskow/', $html );

		$this->assertRegExp( '/Moskau/', $html );
		$this->assertRegExp( '/Hauptstadt/', $html );
	}

	public function testRenderTermBoxForDeleteRevision() {
		$item = $this->getItem();
		$itemId = $item->getId();
		$entityRevisionLookup = $this->getExceptionThrowingEntityRevisionLookup();

		$expander = $this->newExpander( $this->newUser(), $entityRevisionLookup, $itemId );

		$html = $expander->renderTermBox( $itemId, 1 );
		$this->assertEquals( '', $html );
	}

	public function testGetExtraUserLanguages() {
		$item = $this->getItem();
		$itemId = $item->getId();
		$entityRevisionLookup = $this->getEntityRevisionLookup( $item );

		$expander = $this->newExpander( $this->newUser( true ), $entityRevisionLookup, $itemId );
		$this->assertArrayEquals( array(), $expander->getExtraUserLanguages() );

		$expander = $this->newExpander( $this->newUser(), $entityRevisionLookup, $itemId );
		$this->assertArrayEquals( array( 'de', 'ru' ), $expander->getExtraUserLanguages() );
	}

}
