<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use ApiMain;
use FauxRequest;
use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 */
class ApiXmlFormatTest extends \MediaWikiTestCase {

	/**
	 * @var EntityRevision
	 */
	private $entityRevision;

	public function testGetEntitiesXmlFormat() {
		$entityRevision = $this->getEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbgetentities',
			'ids' => $entityId
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\GetEntities', 'wbgetentities', $params );
		$result = $this->doApiRequest( $module );
		$actual = $this->removeEntityAttributes( $result, $entityId );

		$expected = $this->getExpectedGetEntitiesXml( $entityRevision );

		$this->assertXmlStringEqualsXmlString( $expected, $actual );
	}

	private function getExpectedGetEntitiesXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/getentities.xml' ) );

		$expected = $this->replaceIdsInExpectedXml( $xml, $entityRevision );
		$expected = $this->removeEntityAttributes(
			$expected,
			$entityRevision->getEntity()->getId()->getSerialization()
		);

		return $expected;
	}

	public function testGetClaimsXmlFormat() {
		$entityRevision = $this->getEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbgetclaims',
			'entity' => $entityId
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\GetClaims', 'wbgetclaims', $params );
		$actual = $this->doApiRequest( $module );
		$expected = $this->getExpectedGetClaimsXml( $entityRevision );

		$this->assertXmlStringEqualsXmlString( $expected, $actual );
	}

	private function getExpectedGetClaimsXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/getclaims.xml' ) );

		return $this->replaceIdsInExpectedXml( $xml, $entityRevision );
	}

	public function testSetLabelXmlFormat() {
		$entityRevision = $this->getEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbLabel',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetLabel', 'wbsetlabel', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removeEntityAttributes( $result, $entityId );

		$expected = $this->getExpectedSetLabelXml( $entityRevision, '1' );
		$this->assertXmlStringEqualsXmlString( $expected, $actual );
	}

	private function getExpectedSetLabelXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/setlabel.xml' ) );
		return $this->replaceIdsInExpectedXml( $xml, $entityRevision );
	}

	public function testSetDescriptionXmlFormat() {
		$entityRevision = $this->getEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetdescription',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbDescription',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetDescription', 'wbsetdescription', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removeEntityAttributes( $result, $entityId );

		$expected = $this->getExpectedSetDescriptionXml( $entityRevision, '1' );
		$this->assertXmlStringEqualsXmlString( $expected, $actual );
	}

	private function getExpectedSetDescriptionXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/setdescription.xml' ) );
		return $this->replaceIdsInExpectedXml( $xml, $entityRevision );
	}

	public function testSetAliasesXmlFormat() {
		$entityRevision = $this->getEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetaliases',
			'id' => $entityId,
			'language' => 'en-gb',
			'set' => 'AA|BB|CC',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetAliases', 'wbsetaliases', $params, true );
		$result = $this->doApiRequest( $module );
		$actual = $this->removeEntityAttributes( $result, $entityId );

		$expected = $this->getExpectedSetAliasesXml( $entityRevision, '1' );
		$this->assertXmlStringEqualsXmlString( $expected, $actual );
	}

	private function getExpectedSetAliasesXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/setaliases.xml' ) );
		return $this->replaceIdsInExpectedXml( $xml, $entityRevision );
	}

	private function replaceIdsInExpectedXml( $xml, EntityRevision $entityRevision ) {
		$xml = $this->replacePropertyId( $xml, $entityRevision );
		$xml = $this->replaceEntityId(
			$xml,
			$entityRevision->getEntity()->getId()->getSerialization()
		);

		return $xml;
	}

	private function replaceEntityId( $xml, $entityId ) {
		return str_replace( 'Q80050245', $entityId, $xml );
	}

	private function replacePropertyId( $xml, EntityRevision $entityRevision ) {
		/** @var Item $item */
		$item = $entityRevision->getEntity();
		foreach ( $item->getStatements()->getPropertyIds() as $propertyId ) {
			return str_replace( 'P1491009', $propertyId->getSerialization(), $xml );
		}
		return $xml;
	}

	private function removeEntityAttributes( $xml, $entityId ) {
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $xml );

		$xpath = new \DOMXPath( $dom );
		$element = $xpath->query( "//*[@id='$entityId']" )->item( 0 );

		$attributesToRemove = array( 'pageid', 'lastrevid', 'modified', 'title', 'ns' );

		foreach ( $attributesToRemove as $attributeToRemove ) {
			$element->removeAttribute( $attributeToRemove );
		}

		return $dom->saveXML();
	}

	/**
	 * @param string $moduleClass
	 * @param string $moduleName
	 * @param array $params
	 * @param bool $needsToken
	 *
	 * @return ApiMain
	 */
	private function getApiModule( $moduleClass, $moduleName, array $params, $needsToken = false ) {
		if ( $needsToken ) {
			global $wgUser;
			$params['token'] = $wgUser->getEditToken();
		}
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		return new $moduleClass( $main, $moduleName );
	}

	private function doApiRequest( ApiBase $module ) {
		$printer = $module->getMain()->createPrinterByName( 'xml' );
		$module->getResult()->setRawMode( $printer->getNeedsRawData() );
		$module->execute();

		$printer->initPrinter();
		$printer->disable();

		$printer->execute();

		return $printer->getBuffer();
	}

	private function getEntityRevision() {
		if ( !isset( $this->entityRevision ) ) {
			$this->entityRevision = $this->addClaim();
		}

		return $this->entityRevision;
	}

	private function addClaim() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$property = Property::newFromType( 'string' );
		$entityRevision = $store->saveEntity( $property, 'testing', $GLOBALS['wgUser'], EDIT_NEW );
		$propertyId = $entityRevision->getEntity()->getId();

		$item = new Item();
		$entityRevision = $store->saveEntity( $item, 'testing', $GLOBALS['wgUser'], EDIT_NEW );
		/** @var Item $item */
		$item = $entityRevision->getEntity();

		$snak = new PropertyNoValueSnak( $propertyId );
		$guid = $item->getId()->getSerialization() . '$kittens';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );
		$entityRevision = $store->saveEntity( $item, 'testing more!', $GLOBALS['wgUser'] );

		return $entityRevision;
	}

}
