<?php

namespace Wikibase\Test;

use ContentHandler;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Title;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityDiff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Content\EntityContentDiff;

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
	 * @return EntityId
	 */
	protected function getDummyId() {
		return new ItemId( 'Q100' );
	}

	/**
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
		$itemContent->getEntity()->addSiteLink( new SiteLink( 'dewiki', 'Berlin' ) );

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

		// @todo this is needed in PropertyContentTest as well
		//       once we have statements in properties
		$contentWithClaim = $this->newEmpty();
		$claim = new Statement( new PropertyNoValueSnak( 83 ) );
		$claim->setGuid( '$testing$' );
		$contentWithClaim->getEntity()->addClaim( $claim );

		$cases['claims'] = array(
			$contentWithClaim,
			array( 'wb-claims' => 1 )
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

		// @todo this is needed in PropertyContentTest as well
		//       once we have statements in properties
		$contentWithClaim = $this->newEmpty();
		$claim = new Statement( new PropertyNoValueSnak( 83 ) );
		$claim->setGuid( '$testing$' );
		$contentWithClaim->getEntity()->addClaim( $claim );

		$cases['claims'] = array(
			$contentWithClaim,
			EntityContent::STATUS_NONE
		);

		$contentWithClaimAndLink = $contentWithClaim->copy();
		$contentWithClaimAndLink->getEntity()->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$cases['statements and links'] = array(
			$contentWithClaimAndLink,
			EntityContent::STATUS_NONE
		);

		return $cases;
	}

	/**
	 * @return EntityContent
	 */
	private function getItemContentWithClaim() {
		/* @var Item $itemWithClaims */
		$itemContentWithClaims = $this->newEmpty();
		$itemWithClaims = $itemContentWithClaims->getItem();

		$claim = new Statement( new PropertyNoValueSnak( new PropertyId( 'P11' ) ) );
		$claim->setGuid( 'Whatever' );

		$itemWithClaims->setClaims( new Claims( array(
			$claim
		) ) );

		return $itemContentWithClaims;
	}

	/**
	 * @return EntityContent
	 */
	private function getItemContentWithSiteLink() {
		/* @var Item $itemWithSiteLinks */
		$itemContentWithSiteLinks = $this->newEmpty();
		$itemWithSiteLinks = $itemContentWithSiteLinks->getItem();

		$itemWithSiteLinks->setSiteLinkList( new SiteLinkList( array(
			new SiteLink( 'enwiki', 'Foo' )
		) ) );

		return $itemContentWithSiteLinks;
	}

	public function provideGetEntityPageProperties() {
		$cases = parent::provideGetEntityPageProperties();

		// expect wb-sitelinks => 0 for all inherited cases
		foreach ( $cases as &$case ) {
			$case[1]['wb-sitelinks'] = 0;
		}

		$cases['claims'] = array(
			$this->getItemContentWithClaim(),
			array(
				'wb-claims' => 1,
				'wb-sitelinks' => 0,
			)
		);

		$cases['sitelinks'] = array(
			$this->getItemContentWithSiteLink(),
			array(
				'wb-claims' => 0,
				'wb-sitelinks' => 1,
				'wb-status' => ItemContent::STATUS_LINKSTUB,
			)
		);

		return $cases;
	}

	private function handlerSupportsRedirects() {
		$handler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		return $handler->supportsRedirects();
	}

	public function diffProvider() {
		$cases = parent::diffProvider();

		if ( !$this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			return $cases;
		}

		$q10 = new ItemId( 'Q10' );
		$empty = $this->newEmpty( $q10 );

		$spam = $this->newEmpty( $q10 );
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$redir = $this->newRedirect( $q10, new ItemId( 'Q17' ) );
		$redirTarget = 'Q17';

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

		$cases['same redir'] = array( $redir, $redir, new EntityContentDiff(
			new EntityDiff(),
			new Diff()
		) );
		$cases['empty to redir'] = array( $empty, $redir, $emptyToRedirDiff );
		$cases['entity to redir'] = array( $spam, $redir, $spamToRedirDiff );
		$cases['redir to entity'] = array( $redir, $spam, $redirToSpamDiff );

		return $cases;
	}

	public function patchedCopyProvider() {
		$cases = parent::patchedCopyProvider();

		if ( !$this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			return $cases;
		}

		$q10 = new ItemId( 'Q10' );
		$empty = $this->newEmpty( $q10 );

		$spam = $this->newEmpty( $q10 );
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$redirTarget = 'Q17';
		$redir = $this->newRedirect( $q10, new ItemId( $redirTarget ) );

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

		if ( !$this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			return $cases;
		}

		$redir = $this->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) );

		$cases['redirect'] = array( $redir );

		return $cases;
	}

	public function equalsProvider() {
		$cases = parent::equalsProvider();

		if ( !$this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			return $cases;
		}

		$redir = $this->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q7' ) );

		$labels1 = $this->newEmpty();
		$labels1->getEntity()->setLabel( 'en', 'Foo' );

		$cases['same redirect'] = array( $redir, $redir, true );
		$cases['redirect vs labels'] = array( $redir, $labels1, false );
		$cases['labels vs redirect'] = array( $labels1, $redir, false );

		return $cases;
	}

	public function testGetParserOutput_redirect() {
		if ( !$this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$content = $this->newRedirect( new ItemId( 'Q5' ), new ItemId( 'Q123' ) );

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$html = $parserOutput->getText();

		$this->assertTag( array( 'tag' => 'div', 'class' => 'redirectMsg' ), $html, 'redirect message' );
		$this->assertTag( array( 'tag' => 'a', 'content' => 'Q123' ), $html, 'redirect target' );
	}

	public function provideGetEntityId() {
		$q11 = new ItemId( 'Q11' );
		$q12 = new ItemId( 'Q12' );

		$cases = array();
		$cases['entity id'] = array( $this->newEmpty( $q11 ), $q11 );

		if ( $this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$cases['redirect id'] = array( $this->newRedirect( $q11, $q12 ), $q11 );
		}

		return $cases;
	}

	public function entityRedirectProvider() {
		$cases = parent::entityRedirectProvider();

		if ( !$this->handlerSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			return $cases;
		}

		$cases['redirect'] = array(
			$this->newRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) ),
			new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) )
		);

		return $cases;
	}

}
