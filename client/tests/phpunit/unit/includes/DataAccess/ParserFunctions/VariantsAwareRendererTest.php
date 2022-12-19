<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\ParserFunctions;

use Wikibase\Client\DataAccess\ParserFunctions\LanguageAwareRenderer;
use Wikibase\Client\DataAccess\ParserFunctions\VariantsAwareRenderer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\DataAccess\ParserFunctions\VariantsAwareRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class VariantsAwareRendererTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider renderProvider
	 */
	public function testRender(
		EntityId $itemId,
		$propertyLabel,
		array $variants,
		$expected
	) {
		$languageRenderers = [];

		foreach ( $variants as $variant ) {
			$languageRenderers[$variant] = $this->getLanguageAwareRenderer( $variant );
		}

		$variantsRenderer = new VariantsAwareRenderer(
			$languageRenderers,
			$variants
		);

		$result = $variantsRenderer->render( $itemId, $propertyLabel );

		$this->assertSame( $expected, $result );
	}

	public function renderProvider() {
		$itemId = new ItemId( 'Q3' );

		return [
			'multiple variants' => [
				$itemId,
				'cat',
				[ 'zh', 'zh-hans' ],
				'-{zh:cat in zh;zh-hans:cat in zh-hans;}-',
			],

			// No need for the problematic -{…}- variant syntax if there is only one to pick from.
			'single variant' => [
				$itemId,
				'cat',
				[ 'zh' ],
				'cat in zh',
			],

			// Don't create "-{}-" for empty input,
			// to keep the ability to check a missing property with {{#if: }}.
			'zero variants' => [
				$itemId,
				'cat',
				[],
				'',
			],

			// Skip the -{…}- language variant syntax if all possible values are the same anyway.
			'identical values' => [
				$itemId,
				'url',
				[ 'zh', 'zh-hans' ],
				'http://wikipedia.de',
			],
		];
	}

	/**
	 * @param string $languageCode
	 *
	 * @return LanguageAwareRenderer
	 */
	private function getLanguageAwareRenderer( $languageCode ) {
		$languageRenderer = $this->createMock( LanguageAwareRenderer::class );

		$languageRenderer->method( 'render' )
			->willReturnCallback(
				function ( EntityId $entityId, $propertyLabelOrId ) use ( $languageCode ) {
					if ( $propertyLabelOrId === 'url' ) {
						return 'http://wikipedia.de';
					}

					return "$propertyLabelOrId in $languageCode";
				}
			);

		return $languageRenderer;
	}

}
