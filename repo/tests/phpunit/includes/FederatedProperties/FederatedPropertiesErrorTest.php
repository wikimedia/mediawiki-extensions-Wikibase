<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesError;

/**
 * @covers \Wikibase\Repo\FederatedProperties\FederatedPropertiesError
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesErrorTest extends MediaWikiIntegrationTestCase {

	public function testOutputShouldGenerateErrorPage() {
		$languageCode = 'en';
		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( $languageCode, 'A label' );
		$params = [];

		$e = new FederatedPropertiesError( $languageCode, $item, 'key', $params );
		$this->assertInstanceOf( \RawMessage::class, $e->msg );

		$this->assertContainsEquals( $e->msg->parse(), [
			'<div class="mw-message-box-error errorbox mw-message-box">⧼key⧽</div>', // deprecated, will be removed
			'<div class="mw-message-box-error mw-message-box">⧼key⧽</div>', // use this with assertSame later
		] );

		$this->assertStringContainsString(
			'<span class="wikibase-title-id">(Q1)</span>',
			$e->title->parse()
		);

		$this->assertStringContainsString(
			'<span class="wikibase-title-label">A label</span>',
			$e->title->parse()
		);
	}

	public function testOutputShouldGenerateMissingLabel() {
		$languageCode = 'en';
		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'de', 'Ein label' );
		$params = [];

		$e = new FederatedPropertiesError( $languageCode, $item, 'key', $params );

		$this->assertContainsEquals( $e->msg->parse(), [
			'<div class="mw-message-box-error errorbox mw-message-box">⧼key⧽</div>', // deprecated, will be removed
			'<div class="mw-message-box-error mw-message-box">⧼key⧽</div>', // use this with assertSame later
		] );

		$this->assertStringContainsString(
			'<span class="wikibase-title-label">' . wfMessage( 'wikibase-label-empty' )->parse() . '</span>',
			$e->title->parse()
		);
	}
}
