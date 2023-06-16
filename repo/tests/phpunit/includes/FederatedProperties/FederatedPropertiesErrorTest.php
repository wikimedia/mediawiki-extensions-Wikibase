<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWikiTestCase;
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
class FederatedPropertiesErrorTest extends MediaWikiTestCase {

	public function testOutputShouldGenerateErrorPage() {
		$languageCode = 'en';
		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( $languageCode, 'A <b>label</b>' );
		$params = [];

		$e = new FederatedPropertiesError( $languageCode, $item, 'key', $params );
		$this->assertInstanceOf( \RawMessage::class, $e->msg );

		$this->assertEquals( $e->msg->parse(), '<div class="errorbox">⧼key⧽</div>' );

		$this->assertStringContainsString(
			'<span class="wikibase-title-id">(Q1)</span>',
			$e->title->parse()
		);

		$this->assertStringContainsString(
			'<span class="wikibase-title-label">A &lt;b&gt;label&lt;/b&gt;</span>',
			$e->title->parse()
		);
	}

	public function testOutputShouldGenerateMissingLabel() {
		$languageCode = 'en';
		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'de', 'Ein label' );
		$params = [];

		$e = new FederatedPropertiesError( $languageCode, $item, 'key', $params );

		$this->assertEquals( $e->msg->parse(), '<div class="errorbox">⧼key⧽</div>' );

		$this->assertStringContainsString(
			'<span class="wikibase-title-label">' . wfMessage( 'wikibase-label-empty' )->parse() . '</span>',
			$e->title->parse()
		);
	}
}
