<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use RequestContext;
use Title;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Hook\OutputPageJsConfigHookHandler;
use Wikibase\ItemContent;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\Settings;

/**
 * @covers Wikibase\Hook\OutputPageJsConfigHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( array $expected, EntityId $entityId, array $cachedConfig,
		array $parserConfig, Settings $settings, $experimental, $message
	) {
		$hookHandler = new OutputPageJsConfigHookHandler(
			$this->getEntityContentFactory(),
			$this->getParserOutputJsConfigBuilder( $parserConfig ),
			$settings,
			array( 'de', 'en', 'es', 'fr' )
		);

		$title = $this->getTitleForId( $entityId );

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();
		$output->addJsConfigVars( $cachedConfig );

		$hookHandler->handle( $output, $experimental );

		$configVars = $output->getJsConfigVars();

		$this->assertEquals( $experimental, $configVars['wbExperimentalFeatures'], 'experimental' );
		$this->assertEquals( $expected, array_keys( $configVars ), $message );
	}

	public function handleProvider() {
		$settings = $this->getSettings();
		$entityId = new ItemId( 'Q4' );

		$parserConfig = array(
			'wbEntityId' => $entityId->getSerialization()
		);

		$expected = array(
			'wbEntityId',
			'wbUserIsBlocked',
			'wbUserCanEdit',
			'wbCopyright',
			'wbExperimentalFeatures'
		);

		return array(
			array( $expected, $entityId, $parserConfig, $parserConfig,
				$settings, true, 'config vars with parser cache' ),
			array( $expected, $entityId, array(), $parserConfig,
				$settings, true, 'config vars without parser cache' )
		);
	}

	/**
	 * @return Settings
	 */
	private function getSettings() {
		$settings = new Settings();
		$settings->setSetting( 'dataRightsUrl', 'https://creativecommons.org' );
		$settings->setSetting( 'dataRightsText', 'CC-0' );

		return $settings;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getTitleForId( EntityId $entityId ) {
		$name = $entityId->getEntityType() . ':' . $entityId->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return ParserOutputJsConfigBuilder
	 */
	private function getParserOutputJsConfigBuilder( $configVars ) {
		$configBuilder = $this->getMockBuilder( 'Wikibase\ParserOutputJsConfigBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnCallback(
				function() use( $configVars ) {
					return $configVars;
				}
			)
		);

		return $configBuilder;
	}

	/**
	 * @return EntityContentFactory
	 */
	private function getEntityContentFactory() {
		$entityContentFactory = $this->getMockBuilder( 'Wikibase\EntityContentFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityContentFactory->expects( $this->any() )
			->method( 'getFromRevision' )
			->will( $this->returnCallback( array( $this, 'getEntityContent' ) ) );

		$entityContentFactory->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $entityContentFactory;
	}

	/**
	 * @return EntityContent
	 */
	public function getEntityContent() {
		$item = Item::newFromArray( array() );

		$itemId = new ItemId( 'Q5881' );
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Cake' );

		$snak = new PropertyValueSnak( new PropertyId( 'P794' ), new StringValue( 'a' ) );

		$claim = new Claim( $snak );
		$claim->setGuid( 'P794$muahahaha' );

		$item->addClaim( $claim );

		$entityContent = new ItemContent( $item );

		return $entityContent;
	}

}
