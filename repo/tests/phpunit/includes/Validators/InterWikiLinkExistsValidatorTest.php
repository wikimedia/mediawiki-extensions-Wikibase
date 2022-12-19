<?php

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use Wikibase\Repo\Validators\InterWikiLinkExistsValidator;

/**
 * @covers \Wikibase\Repo\Validators\InterWikiLinkExistsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
class InterWikiLinkExistsValidatorTest extends \PHPUnit\Framework\TestCase {

	private const EXISTING_PAGE = "Foo.map";
	private const NONEXISTENT_PAGE = "Foo.NOT-FOUND.map";

	/**
	 * @return MediaWikiPageNameNormalizer
	 */
	private function getMediaWikiPageNameNormalizer() {
		$pageNormalizer = $this->createMock( MediaWikiPageNameNormalizer::class );

		$pageNormalizer->method( 'normalizePageName' )
			->with( $this->isType( 'string' ), $this->anything() )
			->willReturnCallback( function( $pageName ) {
				return $pageName === self::EXISTING_PAGE ? $pageName : false;
			} );

		return $pageNormalizer;
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $expected, $value ) {
		$validator = new InterWikiLinkExistsValidator(
			$this->getMediaWikiPageNameNormalizer(),
			'http://does-not.matter'
		);

		$this->assertSame(
			$expected,
			$validator->validate( $value )->isValid()
		);
	}

	public function provideValidate() {
		return [
			"Valid, plain string" => [
				true, self::EXISTING_PAGE,
			],
			"Valid, StringValue" => [
				true, new StringValue( self::EXISTING_PAGE ),
			],
			"Invalid, StringValue" => [
				false, new StringValue( self::NONEXISTENT_PAGE ),
			],
		];
	}

	public function testValidate_noString() {
		$validator = new InterWikiLinkExistsValidator(
			$this->getMediaWikiPageNameNormalizer(),
			'http://does-not.matter/'
		);

		$this->expectException( InvalidArgumentException::class );
		$validator->validate( 5 );
	}

}
