<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Title;
use Wikibase\DataModel\Entity\EntityContentDiff;
use Wikibase\DataModel\Entity\EntityDiff;
use Wikibase\DataModel\Entity\ItemId;
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
 * @author Daniel Kinzler
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

		$cases['sitelinks'] = array(
			array( 'links' => array( 'enwiki' => array( 'name' => 'Foo', 'badges' => array() ) ) ),
			array( 'wb-claims' => 0, 'wb-sitelinks' => 1 )
		);

		return $cases;
	}

	public function provideGetEntityStatus() {
		$cases = parent::provideGetEntityStatus();

		$links = array( 'enwiki' => array( 'name' => 'Foo', 'badges' => array() ) );

		$cases['linkstub'] = array(
			array( 'links' => $links ),
			ItemContent::STATUS_LINKSTUB
		);

		$cases['linkstub with terms'] = array(
			array(
				'label' => array( 'en' => 'Foo' ),
				'links' => $links
			),
			ItemContent::STATUS_LINKSTUB
		);

		$cases['statements and links'] = $cases['claims']; // from parent::provideGetEntityStatus();
		$cases['statements and links'][0]['links'] = $links;

		return $cases;
	}

	public function provideGetEntityPageProperties() {
		$cases = parent::provideGetEntityPageProperties();

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

	public function diffProvider() {
		$cases = parent::diffProvider();

		$empty = $this->newEmpty();

		$spam = $this->newEmpty();
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$redir = $this->newRedirect( new ItemId( 'Q17' ) );
		$redirTarget = $redir->getRedirectTarget()->getFullText();

		$emptyToRedirDiff = new EntityContentDiff(
			new EntityDiff( array() ),
			new Diff( array(
				'redirect' => new DiffOpAdd( $redirTarget ),
			), true )
		);

		$spamToRedirDiff = new EntityContentDiff(
			new EntityDiff( array(
				'label' => new Diff(
						array( 'en' => new DiffOpRemove( 'Spam' ) )
					),
			) ),
			new Diff( array(
				'redirect' => new DiffOpAdd( $redirTarget ),
			), true )
		);

		$redirToSpamDiff = new EntityContentDiff(
			new EntityDiff( array(
				'label' => new Diff(
						array( 'en' => new DiffOpAdd( 'Spam' ) )
					),
			) ),
			new Diff( array(
				'redirect' => new DiffOpRemove( $redirTarget ),
			), true )
		);

		$cases['same redir'] = array( $redir, $redir, new EntityContentDiff() );
		$cases['empty to redir'] = array( $empty, $redir, $emptyToRedirDiff );
		$cases['entity to redir'] = array( $spam, $redir, $spamToRedirDiff );
		$cases['redir to entity'] = array( $redir, $spam, $redirToSpamDiff );

		return $cases;
	}

	public function patchedCopyProvider() {
		$cases = parent::patchedCopyProvider();

		$empty = $this->newEmpty();

		$spam = $this->newEmpty();
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$redir = $this->newRedirect( new ItemId( 'Q17' ) );
		$redirTarget = $redir->getRedirectTarget()->getFullText();

		$emptyToRedirDiff = new EntityContentDiff(
			new EntityDiff( array() ),
			new Diff( array(
				'redirect' => new DiffOpAdd( $redirTarget ),
			), true )
		);

		$spamToRedirDiff = new EntityContentDiff(
			new EntityDiff( array(
				'label' => new Diff(
						array( 'en' => new DiffOpRemove( 'Spam' ) )
					),
			) ),
			new Diff( array(
				'redirect' => new DiffOpAdd( $redirTarget ),
			), true )
		);

		$redirToSpamDiff = new EntityContentDiff(
			new EntityDiff( array(
				'label' => new Diff(
						array( 'en' => new DiffOpAdd( 'Spam' ) )
					),
			) ),
			new Diff( array(
				'redirect' => new DiffOpRemove( $redirTarget ),
			), true )
		);

		$cases['empty to redir'] = array( $empty, $emptyToRedirDiff, $redir );
		$cases['entity to redir'] = array( $spam, $spamToRedirDiff, $redir );
		$cases['redir to entity'] = array( $redir, $redirToSpamDiff, $spam );
		$cases['redir with entity clash'] = array( $spam, $emptyToRedirDiff, null );

		return $cases;
	}

	public function copyProvider() {
		$cases = parent::copyProvider();

		$redir = $this->newRedirect( new ItemId( 'Q7' ) );

		$cases['redirect'] = array( $redir );

		return $cases;
	}

	public function equalsProvider() {
		$cases = parent::equalsProvider();

		$redir = $this->newRedirect( new ItemId( 'Q7' ) );

		$labels1 = $this->newEmpty();
		$labels1->getEntity()->setLabel( 'en', 'Foo' );

		$cases['same redirect'] = array( $redir, $redir, true );
		$cases['redirect vs labels'] = array( $redir, $labels1, false );
		$cases['labels vs redirect'] = array( $labels1, $redir, false );

		return $cases;
	}

	public function testGetParserOutput_redirect() {
		$content = $this->newRedirect( new ItemId( 'Q123' ) );

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$html = $parserOutput->getText();

		$this->assertTag( array( 'tag' => 'div', 'class' => 'redirectMsg' ), $html, 'redirect message' );
		$this->assertTag( array( 'tag' => 'a', 'content' => 'Q123' ), $html, 'redirect target' );
	}

}
