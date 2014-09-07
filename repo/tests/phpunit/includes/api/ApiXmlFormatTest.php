<?php

namespace Wikibase\Test\Api;

use ApiMain;
use FauxRequest;
use Wikibase\Api\GetEntities;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lib\ClaimGuidGenerator;
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

		$params = array(
			'action' => 'wbgetentities',
			'ids' => $entityRevision->getEntity()->getId()->getSerialization()
		);

		$module = $this->getApiModule( '\Wikibase\Api\GetEntities', 'wbgetentities', $params );
		$result = $this->doApiRequest( $module );

		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $result );

		$apiNode = $dom->getElementsByTagName( 'api' )->item( 0 );
		$success = $apiNode->getAttribute( 'success' );
		$this->assertEquals( 1, $success, 'api success' );

		$entitiesNode = $apiNode->firstChild;
		$this->assertEquals( 'entities', $entitiesNode->nodeName, 'entities node name' );
		$this->assertEquals( 1, $entitiesNode->childNodes->length, 'entities length' );

		$entityNode = $entitiesNode->firstChild;
		$this->assertEquals( 'entity', $entityNode->nodeName, 'assert entity node' );
		$this->assertRegExp( '/^Q\d+$/', $entityNode->getAttribute( 'id' ), 'entity node has id' );

		foreach( $entityNode->childNodes as $childNode ) {
			$childNodeNames[] = $childNode->nodeName;
		}

		$expected = array( 'aliases', 'labels', 'descriptions', 'claims', 'sitelinks' );
		$this->assertEquals( $expected, $childNodeNames, 'entity child nodes' );
	}

	public function testGetClaimsXmlFormat() {
		$entityRevision = $this->getEntityRevision();

		$params = array(
			'action' => 'wbgetclaims',
			'entity' => $entityRevision->getEntity()->getId()->getSerialization()
		);

		$module = $this->getApiModule( '\Wikibase\Api\GetClaims', 'wbgetclaims', $params );
		$result = $this->doApiRequest( $module );

		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXml( $result );

		$apiNode = $dom->getElementsByTagName( 'api' )->item( 0 );
		$claimsNode = $apiNode->firstChild;

		$this->assertEquals( 'claims', $claimsNode->nodeName, 'claims (property group) node name' );

		// see bug 70531 about extra 'claim' node, which makes this equal 2
		$this->assertGreaterThanOrEqual( 1, $claimsNode->childNodes->length, 'number of properties' );

		$propertyNode = $claimsNode->firstChild;
		$this->assertEquals( 'claim', $propertyNode->nodeName );
		$this->assertEquals( 1, $propertyNode->childNodes->length, 'number of claims' );
	}

	private function getApiModule( $moduleClass, $moduleName, array $params ) {
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		return new $moduleClass( $main, $moduleName );
	}

	private function doApiRequest( $module ) {
		$printer = $module->getMain()->createPrinterByName( 'xml' );
		$module->getResult()->setRawMode( $printer->getNeedsRawData() );
		$module->execute();

		$printer->setUnescapeAmps( false );
		$printer->initPrinter( false );

		ob_start();
		$printer->execute();
		$output = ob_get_clean();

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

		$guidGenerator = new ClaimGuidGenerator();
		$guid = $guidGenerator->newGuid( $item->getId() );

		$statement->setGuid( $guid );
		$item->addClaim( $statement );
		$entityRevision = $store->saveEntity( $item, 'testing more!', $GLOBALS['wgUser'] );

		return $entityRevision;
	}

}
