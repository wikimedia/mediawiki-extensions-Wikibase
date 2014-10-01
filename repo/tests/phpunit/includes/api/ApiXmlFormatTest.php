<?php

namespace Wikibase\Test\Api;

use ApiBase;
use ApiMain;
use Exception;
use FauxRequest;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiXmlFormatTest extends \PHPUnit_Framework_TestCase {

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

		$module = $this->getApiModule( '\Wikibase\Api\GetEntities', 'wbgetentities', $params );
		$result = $this->doApiRequest( $module );
		$actual = $this->removeGetEntitiesAttributes( $result, $entityId );

		$expected = $this->getExpectedGetEntitiesXml( $entityRevision );

		$this->assertEquals( $expected, $actual );
	}

	private function getExpectedGetEntitiesXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/getentities.xml' ) );

		$expected = $this->replaceIdsInExpectedXml( $xml, $entityRevision );
		$expected = $this->removeGetEntitiesAttributes(
			$expected,
			$entityRevision->getEntity()->getId()->getSerialization()
		);

		return $expected;
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
		$propertyIds = $entityRevision->getEntity()->getStatements()->getPropertyIds();

		foreach( $propertyIds as $propertyId ) {
			$propertyIdText = $propertyId->getSerialization();
		}

		return str_replace( 'P1491009', $propertyIdText, $xml );
	}

	private function removeGetEntitiesAttributes( $xml, $entityId ) {
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $xml );

		$xpath = new \DOMXPath( $dom );
		$element = $xpath->query( "//*[@id='$entityId']" )->item( 0 );

		$attributesToRemove = array( 'pageid', 'lastrevid', 'modified', 'title', 'ns' );

		foreach( $attributesToRemove as $attributeToRemove ) {
			$element->removeAttribute( $attributeToRemove );
		}

		return $dom->saveXML();
	}

	public function testGetClaimsXmlFormat() {
		$entityRevision = $this->getEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbgetclaims',
			'entity' => $entityId
		);

		$module = $this->getApiModule( '\Wikibase\Api\GetClaims', 'wbgetclaims', $params );
		$actual = $this->doApiRequest( $module );
		$expected = $this->getExpectedGetClaimsXml( $entityRevision );

		$this->assertEquals( $expected, $actual );
	}

	private function getExpectedGetClaimsXml( EntityRevision $entityRevision ) {
		$xml = trim( file_get_contents( __DIR__ . '/../../data/api/getclaims.xml' ) );

		return $this->replaceIdsInExpectedXml( $xml, $entityRevision );
	}

	private function getApiModule( $moduleClass, $moduleName, array $params ) {
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		return new $moduleClass( $main, $moduleName );
	}

	private function doApiRequest( ApiBase $module ) {
		$printer = $module->getMain()->createPrinterByName( 'xml' );
		$module->getResult()->setRawMode( $printer->getNeedsRawData() );
		$module->execute();

		$printer->setUnescapeAmps( false );
		$printer->initPrinter( false );

		ob_start();

		try {
			$printer->execute();
			$output = ob_get_clean();
		} catch ( Exception $ex ) {
			ob_end_clean();
			throw $ex;
		}

		$printer->closePrinter();

		return $output;
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

		$item = Item::newEmpty();
		$entityRevision = $store->saveEntity( $item, 'testing', $GLOBALS['wgUser'], EDIT_NEW );
		$item = $entityRevision->getEntity();

		$snak = new PropertyNoValueSnak( $propertyId->getNumericId() );
		$statement = new Statement( $snak );

		$statement->setGuid( $item->getId()->getSerialization() . '$kittens' );
		$item->addClaim( $statement );
		$entityRevision = $store->saveEntity( $item, 'testing more!', $GLOBALS['wgUser'] );

		return $entityRevision;
	}

}
