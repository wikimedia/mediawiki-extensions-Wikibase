<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use DataValues\StringValue;
use Language;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer;
use Wikibase\DataAccess\PropertyParserFunction\SnaksFinder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Client\Usage\UsageAccumulator;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class LanguageAwareRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param array $usages
	 *
	 * @return UsageAccumulator
	 */
	private function getUsageAccumulator( array &$usages ) {
		$mock = $this->getMockBuilder( 'Wikibase\Client\Usage\UsageAccumulator' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'addLabelUsage' )
			->will( $this->returnCallback(
				function ( EntityId $id ) use ( &$usages ) {
					$usages[] = new EntityUsage( $id, EntityUsage::LABEL_USAGE );
				}
			) );

		$mock->expects( $this->never() )
			->method( 'addAllUsage' );

		$mock->expects( $this->never() )
			->method( 'addSiteLinksUsage' );

		return $mock;
	}

	/**
	 * @param SnaksFinder $snaksFinder
	 * @param string $languageCode
	 * @param array &$usages
	 *
	 * @return LanguageAwareRenderer
	 */
	private function getRenderer( SnaksFinder $snaksFinder, $languageCode, array &$usages = array() ) {
		$targetLanguage = Language::factory( $languageCode );

		return new LanguageAwareRenderer(
			$targetLanguage,
			$snaksFinder,
			$this->getSnakFormatter(),
			$this->getUsageAccumulator( $usages )
		);
	}

	public function testRender() {
		$propertyId = new PropertyId( 'P1337' );
		$snaks = array(
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) )
		);

		$usages = array();
		$renderer = $this->getRenderer( $this->getSnaksFinder( $snaks ), 'en', $usages );

		$q42 = new ItemId( 'Q42' );
		$result = $renderer->render( $q42, 'p1337' );

		$expected = 'a kitten!, two kittens!!';
		$this->assertEquals( $expected, $result );
	}

	public function testRender_trackUsage() {
		$q22 = new ItemId( 'Q22' );
		$q23 = new ItemId( 'Q23' );
		$propertyId = new PropertyId( 'P1337' );
		$snaks = array(
			'Q42$22' => new PropertyValueSnak( $propertyId, new EntityIdValue( $q22 ) ),
			'Q42$23' => new PropertyValueSnak( $propertyId, new EntityIdValue( $q23 ) )
		);

		$usages = array();
		$renderer = $this->getRenderer( $this->getSnaksFinder( $snaks ), 'en', $usages );

		$q42 = new ItemId( 'Q42' );
		$renderer->render( $q42, 'p1337' );

		$expectedUsage = array(
			new EntityUsage( $q22, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q23, EntityUsage::LABEL_USAGE ),
		);

		$this->assertSameUsages( $expectedUsage, $usages );
	}

	/**
	 * @param EntityUsage[] $expected
	 * @param EntityUsage[] $actual
	 * @param string $message
	 */
	private function assertSameUsages( array $expected, array $actual, $message = '' ) {
		$expected = $this->getUsageStrings( $expected );
		$actual = $this->getUsageStrings( $actual );

		$this->assertEquals( $expected, $actual, $message );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	private function getUsageStrings( array $usages ) {
		return array_values(
			array_map( function( EntityUsage $usage ) {
				return $usage->getIdentityString();
			}, $usages )
		);
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return SnaksFinder
	 */
	private function getSnaksFinder( array $snaks ) {
		$snaksFinder = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\SnaksFinder'
			)
			->disableOriginalConstructor()
			->getMock();

		$snaksFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnValue( $snaks ) );

		return $snaksFinder;
	}

	public function testRenderForPropertyNotFound() {
		$renderer = $this->getRenderer( $this->getSnaksFinderForPropertyNotFound(), 'qqx' );
		$result = $renderer->render( new ItemId( 'Q4' ), 'invalidLabel' );

		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	/***
	 * @return SnaksFinder
	 */
	private function getSnaksFinderForPropertyNotFound() {
		$snaksFinder = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\SnaksFinder'
			)
			->disableOriginalConstructor()
			->getMock();

		$snaksFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnCallback( function() {
				throw new PropertyLabelNotResolvedException( 'invalidLabel', 'qqx' );
			} )
		);

		return $snaksFinder;
	}

	/***
	 * @return SnakFormatter
	 */
	private function getSnakFormatter() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback(
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
			) );

		return $snakFormatter;
	}

}
