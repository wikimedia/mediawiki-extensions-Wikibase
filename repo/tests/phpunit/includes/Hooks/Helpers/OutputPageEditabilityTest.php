<?php

namespace Wikibase\Repo\Tests\Hooks\Helpers;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\SimpleAuthority;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Request\FauxResponse;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentityValue;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;

/**
 * @covers \Wikibase\Repo\Hooks\Helpers\OutputPageEditability
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OutputPageEditabilityTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Disabling to prevent CentralAuth from accessing the DB.
		$this->clearHook( 'UserGetRights' );
	}

	/**
	 * @dataProvider nonEditableOutputPageProvider
	 */
	public function testGivenPageIsNotEditable_returnsFalse( OutputPage $outputPage ) {
		$editability = new OutputPageEditability();
		$this->assertFalse( $editability->validate( $outputPage ) );
	}

	public function nonEditableOutputPageProvider() {
		$user = new UserIdentityValue( 1, __METHOD__ );
		$out = $this->newOutputPage();
		$noRightsPerformer = new SimpleAuthority( $user, [] );
		$noRightsUserContext = new RequestContext();
		$noRightsUserContext->setAuthority( $noRightsPerformer );
		$out->setContext( $noRightsUserContext );
		$out->setTitle( Title::makeTitle( NS_MAIN, 'Test' ) );
		yield 'user does not have edit permission' => [ $out ];

		$request = $this->createMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( true );
		$request->method( 'response' )
			->willReturn( new FauxResponse );
		$context = new RequestContext();
		$context->setAuthority( new UltimateAuthority( $user ) );
		$context->setRequest( $request );
		$context->setTitle( Title::makeTitle( NS_MAIN, __METHOD__ ) );
		yield 'diff page' => [ new OutputPage( $context ) ];

		$out = $this->newOutputPage();
		$out->setRevisionId( 123 );
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 321 );
		$out->setTitle( $title );
		yield 'not latest revision' => [ $out ];

		$out = $this->newOutputPage();
		$context = new RequestContext();
		$context->setAuthority( new UltimateAuthority( $user ) );
		$context->setTitle( Title::makeTitle( NS_MAIN, 'Test' ) );
		$out->setContext( $context );
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

		$user = new UserIdentityValue( 1, __METHOD__ );
		$performer = new UltimateAuthority( $user );

		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$request = $this->createMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setAuthority( $performer );
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

		$user = new UserIdentityValue( 1, __METHOD__ );
		$performer = new UltimateAuthority( $user );

		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$request = $this->createMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setAuthority( $performer );
		$context->setRequest( $request );
		$context->setTitle( $title );

		$outputPage->setRevisionId( null );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNonExistingTitleEditableOutputPage() {
		$outputPage = $this->newOutputPage();

		$user = new UserIdentityValue( 1, __METHOD__ );
		$performer = new UltimateAuthority( $user );

		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( false );

		$request = $this->createMock( WebRequest::class );
		$request->expects( $this->once() )
			->method( 'getCheck' )
			->with( 'diff' )
			->willReturn( false );
		$request->method( 'response' )
			->willReturn( new FauxResponse );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$context->setTitle( $title );
		$context->setAuthority( $performer );

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
		$outputPage = new OutputPage( new RequestContext() );
		$outputPage->setTitle( Title::makeTitle( NS_MAIN, __METHOD__ ) );

		return $outputPage;
	}

}
