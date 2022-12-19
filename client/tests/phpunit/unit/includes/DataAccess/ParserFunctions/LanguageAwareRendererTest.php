<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\DataAccess\ParserFunctions;

use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCaseTrait;
use ParserOutput;
use Title;
use Wikibase\Client\DataAccess\ParserFunctions\LanguageAwareRenderer;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Client\DataAccess\ParserFunctions\LanguageAwareRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class LanguageAwareRendererTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param SnaksFinder $snaksFinder
	 * @param EntityLookup $entityLookup
	 * @param string $languageCode
	 * @param ParserOutput $parserOutput
	 *
	 * @return LanguageAwareRenderer
	 */
	private function getRenderer(
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		EntityLookup $entityLookup,
		$languageCode,
		ParserOutput $parserOutput
	) {
		$targetLanguage = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $languageCode );

		$entityStatementsRenderer = new StatementTransclusionInteractor(
			$targetLanguage,
			$propertyIdResolver,
			$snaksFinder,
			$this->getSnakFormatter(),
			$entityLookup,
			new HashUsageAccumulator()
		);

		return new LanguageAwareRenderer(
			$targetLanguage,
			$entityStatementsRenderer,
			$parserOutput,
			$this->createMock( Title::class )
		);
	}

	/**
	 * Return a mock ParserOutput object that checks how many times it adds a tracking category.
	 * @param $num Number of times a tracking category should be added
	 *
	 * @return ParserOutput
	 */
	private function getMockParserOutput( $num ) {
		$mockParser = $this->getMockBuilder( ParserOutput::class )
			->onlyMethods( [ 'getPageProperty', 'addCategory' ] )
			->getMock();
		$mockParser->expects( $this->exactly( $num ) )
			->method( 'addCategory' );

		return $mockParser;
	}

	public function testRender() {
		$propertyId = new NumericPropertyId( 'P1337' );
		$snaks = [
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) ),
		];

		$renderer = $this->getRenderer(
			$this->getPropertyIdResolver(),
			$this->getSnaksFinder( $snaks ),
			$this->getEntityLookup( 100 ),
			'en',
			$this->getMockParserOutput( 0 )
		);

		$q42 = new ItemId( 'Q42' );
		$result = $renderer->render( $q42, 'p1337' );

		$expected = 'a kitten!, two kittens!!';
		$this->assertEquals( $expected, $result );
	}

	public function testRenderForPropertyNotFound() {
		$renderer = $this->getRenderer(
			$this->getPropertyIdResolverForPropertyNotFound(),
			$this->getSnaksFinder( [] ),
			$this->getEntityLookup( 100 ),
			'qqx',
			$this->getMockParserOutput( 1 )
		);
		$result = $renderer->render( new ItemId( 'Q4' ), 'invalidLabel' );

		$this->assertMatchesRegularExpression(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertMatchesRegularExpression(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	public function testRenderForPropertyNotFound_translated() {
		$renderer = $this->getRenderer(
			$this->getPropertyIdResolverForPropertyNotFound(),
			$this->getSnaksFinder( [] ),
			$this->getEntityLookup( 100 ),
			'de',
			$this->getMockParserOutput( 1 )
		);
		$result = $renderer->render( new ItemId( 'Q4' ), 'invalidLabel' );

		$this->assertStringContainsString(
			wfMessage( 'wikibase-property-render-error' )
				->params( 'invalidLabel' )
				->params(
					wfMessage( 'wikibase-property-notfound' )
						->params( 'invalidLabel', 'de' )
				)
				->inLanguage( 'de' )
				->text(),
			$result
		);
	}

	public function testRender_exceededEntityAccessLimit() {
		$renderer = $this->getRenderer(
			$this->getPropertyIdResolver(),
			$this->getSnaksFinder( [] ),
			$this->getEntityLookup( 1 ),
			'qqx',
			$this->getMockParserOutput( 0 )
		);

		$renderer->render( new ItemId( 'Q3' ), 'tooManyEntities' );
		$result = $renderer->render( new ItemId( 'Q4' ), 'tooManyEntities' );

		$this->assertMatchesRegularExpression(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertMatchesRegularExpression(
			'/wikibase-property-render-error.*tooManyEntities.*/',
			$result
		);
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return SnaksFinder
	 */
	private function getSnaksFinder( array $snaks ) {
		$snaksFinder = $this->createMock( SnaksFinder::class );

		$snaksFinder->method( 'findSnaks' )
			->willReturn( $snaks );

		return $snaksFinder;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolver() {
		$propertyIdResolver = $this->createMock( PropertyIdResolver::class );

		$propertyIdResolver->method( 'resolvePropertyId' )
			->willReturn( new NumericPropertyId( 'P1337' ) );

		return $propertyIdResolver;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolverForPropertyNotFound() {
		$propertyIdResolver = $this->createMock( PropertyIdResolver::class );

		$propertyIdResolver->method( 'resolvePropertyId' )
			->willReturnCallback( function( $propertyLabelOrId, $languageCode ) {
				throw new PropertyLabelNotResolvedException( $propertyLabelOrId, $languageCode );
			} );

		return $propertyIdResolver;
	}

	/**
	 * @param int $entityAccessLimit
	 *
	 * @return EntityLookup
	 */
	private function getEntityLookup( $entityAccessLimit ) {
		$lookup = $this->createMock( EntityLookup::class );
		$lookup->method( 'getEntity' )
			->willReturn( $this->createMock( StatementListProvider::class ) );

		return new RestrictedEntityLookup( $lookup, $entityAccessLimit );
	}

	/**
	 * @return SnakFormatter
	 */
	private function getSnakFormatter() {
		$snakFormatter = $this->createMock( SnakFormatter::class );

		$snakFormatter->method( 'formatSnak' )
			->willReturnCallback(
				function ( Snak $snak ) {
					if ( $snak instanceof PropertyValueSnak ) {
						$value = $snak->getDataValue();
						if ( $value instanceof StringValue ) {
							return $value->getValue();
						} elseif ( $value instanceof EntityIdValue ) {
							return $value->getEntityId()->getSerialization();
						} else {
							return '(' . $value->getType() . ')';
						}
					} else {
						return '(' . $snak->getType() . ')';
					}
				}
			);

		$snakFormatter->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_PLAIN );

		return $snakFormatter;
	}

}
