<?php declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration;

use HamcrestPHPUnitIntegration;
use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\Client\Hooks\Formatter\ClientEntityLinkFormatter;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\Hooks\Formatter\ClientEntityLinkFormatter
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ClientEntityLinkFormatterTest extends TestCase {
	use HamcrestPHPUnitIntegration;

	/**
	 * @var LanguageFactory
	 */
	private $languageFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->languageFactory = $this->createMock( LanguageFactory::class );
		$enLanguageMock = $this->getNewEnLanguageMock();
		$this->languageFactory->method( 'getLanguage' )->with( 'en' )->willReturn( $enLanguageMock );
	}

	private function getNewClientEntityLinkFormatter(): ClientEntityLinkFormatter {
		return new ClientEntityLinkFormatter( $this->languageFactory );
	}

	public function testGetHtmlWithoutLabel() {
		$id = new ItemId( 'Q42' );
		$this->assertThatHamcrest(
			$this->getNewClientEntityLinkFormatter()->getHtml( $id, $this->getNewEnLanguageMock() ),
			is( htmlPiece( havingChild(
				havingTextContents( containsString( $id->getSerialization() ) )
			) ) )
		);
	}

	public function testGetHtmlWithLabel() {
		$labelData = [ 'language' => 'en', 'value' => 'foobar' ];

		$this->assertThatHamcrest(
			$this->getNewClientEntityLinkFormatter()->getHtml( new ItemId( 'Q42' ), $this->getNewEnLanguageMock(), $labelData ),
			is( htmlPiece( havingChild(
				havingTextContents( containsString( $labelData['value'] ) )
			) ) )
		);
	}

	public function getTitleAttributeProvider(): \Generator {
		$labelData = [ 'language' => 'en', 'value' => 'label text' ];

		$descriptionData = [ 'language' => 'en', 'value' => 'description text' ];

		yield "With label and description" => [ 'label text | description text', $labelData, $descriptionData ];
		yield "With neither label nor description" => [ null, [], [] ];
		yield "With label only" => [ 'label text', $labelData, [] ];
		yield "With description only" => [ ' | description text', [], $descriptionData ];
	}

	/**
	 * @dataProvider getTitleAttributeProvider
	 */
	public function testGetTitleAttribute( ?string $expectedTitleAttribute, array $labelData, array $descriptionData ) {
		$this->assertEquals( $expectedTitleAttribute,
			$this->getNewClientEntityLinkFormatter()->getTitleAttribute( $this->getNewEnLanguageMock(), $labelData, $descriptionData ) );
	}

	private function getNewEnLanguageMock(): Language {
		$language = $this->createMock( Language::class );
		$language->method( 'getCode' )->willReturn( 'en' );
		$language->method( 'getDirMark' )->willReturn( '' );
		$language->method( 'getDir' )->willReturn( 'ltr' );
		$language->method( 'getHtmlCode' )->willReturn( 'en' );
		return $language;
	}

}
