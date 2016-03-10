<?php

namespace Wikibase\View\Tests;

use MediaWikiTestCase;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\View\EntityViewPlaceholderExpander;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\EntityViewPlaceholderExpander
 *
 * @uses Wikibase\View\EntityTermsView
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
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
		$templateFactory = TemplateFactory::getDefaultInstance();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$idParser = $this->getMockBuilder( EntityIdParser::class )
			->disableOriginalConstructor()
			->getMock();
		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $itemId ) );

		$termsLanguages = [ 'de', 'en', 'ru' ];

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );

		return new EntityViewPlaceholderExpander(
			$templateFactory,
			$title,
			$user,
			'en',
			$idParser,
			$entityRevisionLookup,
			$termsLanguages,
			$languageNameLookup
		);
	}

	/**
	 * @param Item|null $item
	 * @param int $revId
	 *
	 * @return EntityRevisionLookup
	 */
	private function getEntityRevisionLookup( Item $item = null, $revId = 5 ) {
		$revision = ( $item === null ) ? null : new EntityRevision( $item, $revId );

		$entityLookup = $this->getMock( EntityRevisionLookup::class );
		$entityLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnValue( $revision ) );

		return $entityLookup;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function getExceptionThrowingEntityRevisionLookup() {
		$entityLookup = $this->getMock( EntityRevisionLookup::class );
		$entityLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( function() {
				throw new StorageException( 'Entity not found' );
			} )
		);

		return $entityLookup;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function getNullReturningEntityRevisionLookup() {
		$entityLookup = $this->getMock( EntityRevisionLookup::class );
		$entityLookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnValue( null ) );

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
		$user = $this->getMockBuilder( User::class )
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

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( 'Moskow', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-de', $html );
		$this->assertContains( 'Moskau', $html );
		$this->assertContains( 'Hauptstadt Russlands', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-ru', $html );
	}

	public function testRenderTermBoxForDeleteRevision() {
		$item = $this->getItem();
		$itemId = $item->getId();
		$entityRevisionLookup = $this->getExceptionThrowingEntityRevisionLookup();

		$expander = $this->newExpander( $this->newUser(), $entityRevisionLookup, $itemId );

		$html = $expander->renderTermBox( $itemId, 1 );
		$this->assertEquals( '', $html );
	}

	public function testRenderTermBoxForNonEntityRevision() {
		$item = $this->getItem();
		$itemId = $item->getId();
		$entityRevisionLookup = $this->getNullReturningEntityRevisionLookup();

		$expander = $this->newExpander( $this->newUser(), $entityRevisionLookup, $itemId );

		$html = $expander->renderTermBox( $itemId, 1 );
		$this->assertEquals( '', $html );
	}

}
