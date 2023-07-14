<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SetItemLabelRequestTest extends TestCase {

	public function testNew(): void {
		$itemId = 'Q123';
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;

		$request = new SetItemLabelRequest( $itemId, $langCode, $newLabelText, $editTags, $isBot, null, null );
		$this->assertEquals( $itemId, $request->getItemId() );
		$this->assertEquals( $langCode, $request->getLanguageCode() );
		$this->assertEquals( $newLabelText, $request->getLabel() );
		$this->assertEquals( $editTags, $request->getEditTags() );
		$this->assertFalse( $request->isBot() );
	}

	public function testNewWithTrimmedLabel(): void {
		$itemId = 'Q123';
		$langCode = 'en';
		$spaceyLabelText = '     New label     ';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;

		$request = new SetItemLabelRequest( $itemId, $langCode, $spaceyLabelText, $editTags, $isBot, null, null );
		$this->assertEquals( $itemId, $request->getItemId() );
		$this->assertEquals( $langCode, $request->getLanguageCode() );
		$this->assertEquals( 'New label', $request->getLabel() );
		$this->assertEquals( $editTags, $request->getEditTags() );
		$this->assertFalse( $request->isBot() );
	}

}
