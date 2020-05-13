<?php

declare( strict_types = 1 );
namespace Wikibase\Client\Tests\Unit\Hooks;

use PHPUnit\Framework\TestCase;
use Wikibase\Client\Hooks\LoginFormValidErrorMessagesHandler;

/**
 * @covers \Wikibase\Client\Hooks\LoginFormValidErrorMessagesHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LoginFormValidErrorMessagesHandlerTest extends TestCase {

	public function testOnLoginFormValidErrorMessages() {
		$messages = [
			'unrelated-message-1',
			'unrelated-message-2',
		];
		$handler = new LoginFormValidErrorMessagesHandler();

		$handler->onLoginFormValidErrorMessages( $messages );

		$expected = [
			'unrelated-message-1',
			'unrelated-message-2',
			'wikibase-client-data-bridge-login-warning',
		];
		$this->assertSame( $expected, $messages );
	}

	public function testHandle() {
		$messages = [
			'unrelated-message-1',
			'unrelated-message-2',
		];

		LoginFormValidErrorMessagesHandler::handle( $messages );

		$expected = [
			'unrelated-message-1',
			'unrelated-message-2',
			'wikibase-client-data-bridge-login-warning',
		];
		$this->assertSame( $expected, $messages );
	}

}
