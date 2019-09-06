<?php

namespace Wikibase\Repo\Tests\Hooks\Helpers;

use DerivativeContext;
use MediaWiki\MediaWikiServices;
use OutputPage;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use RequestContext;
use Title;
use User;
use WebRequest;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;

/**
 * @covers \Wikibase\Repo\Hooks\Helpers\OutputPageEditability
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OutputPageEditabilityTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider nonEditableOutputPageProvider
	 */
	public function testGivenPageIsNotEditable_returnsFalse( OutputPage $outputPage ) {
		$editability = new OutputPageEditability();
		$this->assertFalse( $editability->validate( $outputPage ) );
	}

	public function nonEditableOutputPageProvider() {
		$out = $this->newOutputPage();
		$noRightsUser = User::newFromName( 'Test' );
		MediaWikiServices::getInstance()->getPermissionManager()
			->overrideUserRightsForTesting( $noRightsUser, [] );
		$noRightsUserContext = new DerivativeContext( RequestContext::getMain() );
		$noRightsUserContext->setUser( $noRightsUser );
		$out->setContext( $noRightsUserContext );
		$out->setTitle( Title::newFromText( 'Test' ) );
		yield 'user does not have edit permission' => [ $out ];

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( true );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( new Title() );
		yield 'diff page' => [ new OutputPage( $context ) ];

		$out = $this->newOutputPage();
		$out->setRevisionId( 123 );
		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 321 );
		$title->method( 'getRestrictions' )->willReturn( [] );
		$out->setTitle( $title );
		yield 'not latest revision' => [ $out ];

		$out = $this->newOutputPage();
		$out->setPrintable();
		yield 'print view' => [ $out ];
	}

	/**
	 * @dataProvider editableOutputPageProvider
	 */
	public function testGivenPageIsEditable_returnsTrue( OutputPage $outputPage ) {
		$editability = new OutputPageEditability();
		$this->assertTrue( $editability->validate( $outputPage ) );
	}

	public function editableOutputPageProvider() {
		yield [ $this->getDefaultEditableOutputPage() ];
		yield [ $this->getNullRevisionEditableOutputPage() ];
		yield [ $this->getNonExistingTitleEditableOutputPage() ];
	}

	private function getDefaultEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$user = User::newFromName( 'TestUser' );
		MediaWikiServices::getInstance()->getPermissionManager()
			->overrideUserRightsForTesting( $user, [ 'edit' ] );

		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );
		$title->method( 'getRestrictions' )->willReturn( [] );

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser( $user );
		$context->setRequest( $request );
		$context->setTitle( $title );

		$outputPage->setRevisionId( 123 );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 123 );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNullRevisionEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$user = User::newFromName( 'TestUser' );
		MediaWikiServices::getInstance()->getPermissionManager()
			->overrideUserRightsForTesting( $user, [ 'edit' ] );

		$title = $this->getMock( Title::class );
		$title->method( 'getRestrictions' )->willReturn( [] );
		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser( $user );
		$context->setRequest( $request );
		$context->setTitle( $title );

		$outputPage->setRevisionId( null );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNonExistingTitleEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$user = User::newFromName( 'TestUser' );
		MediaWikiServices::getInstance()->getPermissionManager()
			->overrideUserRightsForTesting( $user, [ 'edit', 'create' ] );

		$title = $this->getMock( Title::class );
		$title->method( 'getRestrictions' )->willReturn( [] );
		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( false );

		$request = $this->getMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( $title );
		$context->setUser( $user );

		$outputPage->setRevisionId( 123 );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 123 );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	/**
	 * @return OutputPage
	 */
	private function newOutputPage() {
		$outputPage = new OutputPage( new DerivativeContext( RequestContext::getMain() ) );
		$outputPage->setTitle( new Title() );

		return $outputPage;
	}

}
