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
	public function testGivenPageIsNotEditable_returnsFalse( callable $outputPageFactory ) {
		$outputPage = $outputPageFactory( $this );
		$editability = new OutputPageEditability();
		$this->assertFalse( $editability->validate( $outputPage ) );
	}

	public static function nonEditableOutputPageProvider(): iterable {
		$user = new UserIdentityValue( 1, __METHOD__ );
		$outputPageFactory = function() use ( $user ) {
			$out = self::newOutputPage();
			$noRightsPerformer = new SimpleAuthority( $user, [] );
			$noRightsUserContext = new RequestContext();
			$noRightsUserContext->setAuthority( $noRightsPerformer );
			$out->setContext( $noRightsUserContext );
			$out->setTitle( Title::makeTitle( NS_MAIN, 'Test' ) );

			return $out;
		};
		yield 'user does not have edit permission' => [ $outputPageFactory ];

		$context = new RequestContext();
		$context->setAuthority( new UltimateAuthority( $user ) );
		$context->setTitle( Title::makeTitle( NS_MAIN, __METHOD__ ) );
		$outputPageFactory = function ( self $self ) use ( $context ): OutputPage {
			$request = $self->createMock( WebRequest::class );
			$request->expects( self::once() )
				->method( 'getCheck' )
				->with( 'diff' )
				->willReturn( true );
			$request->method( 'response' )
				->willReturn( new FauxResponse );
			$context->setRequest( $request );

			return new OutputPage( $context );
		};
		yield 'diff page' => [ $outputPageFactory ];

		$outputPageFactory = function ( self $self ) use ( $user ): OutputPage {
			$out = self::newOutputPage();
			$out->setRevisionId( 123 );
			$title = $self->createMock( Title::class );
			$title->method( 'getNamespace' )->willReturn( NS_MAIN );
			$title->expects( self::once() )
				->method( 'getLatestRevID' )
				->willReturn( 321 );
			$out->setTitle( $title );

			return $out;
		};
		yield 'not latest revision' => [ $outputPageFactory ];

		$outputPageFactory = function() use ( $user ): OutputPage {
			$out = self::newOutputPage();
			$context = new RequestContext();
			$context->setAuthority( new UltimateAuthority( $user ) );
			$context->setTitle( Title::makeTitle( NS_MAIN, 'Test' ) );
			$out->setContext( $context );
			$out->setPrintable();

			return $out;
		};
		yield 'print view' => [ $outputPageFactory ];
	}

	/**
	 * @dataProvider editableOutputPageProvider
	 */
	public function testGivenPageIsEditable_returnsTrue( callable $outputPageFactory ) {
		$outputPage = $outputPageFactory( $this );

		$editability = new OutputPageEditability();
		$this->assertTrue( $editability->validate( $outputPage ) );
	}

	public static function editableOutputPageProvider(): iterable {
		yield [ fn ( self $self ) => $self->getDefaultEditableOutputPage() ];
		yield [ fn ( self $self ) => $self->getNullRevisionEditableOutputPage() ];
		yield [ fn ( self $self ) => $self->getNonExistingTitleEditableOutputPage() ];
	}

	private function getDefaultEditableOutputPage(): OutputPage {
		$outputPage = $this->newOutputPage();

		$user = new UserIdentityValue( 1, __METHOD__ );
		$performer = new UltimateAuthority( $user );

		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_PROJECT );
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
		$outputPage->setContext( $context );

		$outputPage->setRevisionId( 123 );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 123 );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNullRevisionEditableOutputPage(): OutputPage {
		$outputPage = $this->newOutputPage();

		$user = new UserIdentityValue( 1, __METHOD__ );
		$performer = new UltimateAuthority( $user );

		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_PROJECT );
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
		$outputPage->setContext( $context );

		$outputPage->setRevisionId( null );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private function getNonExistingTitleEditableOutputPage(): OutputPage {
		$outputPage = $this->newOutputPage();

		$user = new UserIdentityValue( 1, __METHOD__ );
		$performer = new UltimateAuthority( $user );

		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_PROJECT );
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
		$outputPage->setContext( $context );

		$outputPage->setRevisionId( 123 );
		$title->expects( $this->once() )
			->method( 'getLatestRevID' )
			->willReturn( 123 );

		$outputPage->setTitle( $title );

		return $outputPage;
	}

	private static function newOutputPage(): OutputPage {
		$outputPage = new OutputPage( new RequestContext() );
		$outputPage->setTitle( Title::makeTitle( NS_MAIN, __METHOD__ ) );

		return $outputPage;
	}

}
