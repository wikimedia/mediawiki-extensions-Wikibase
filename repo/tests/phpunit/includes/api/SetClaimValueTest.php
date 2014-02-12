<?php

namespace Wikibase\Test\Api;

use DataValues\DataValue;
use DataValues\StringValue;
use Revision;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\EntityIdLinkFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\DataModel\Entity\Property;
use Wikibase\PropertyContent;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\SetClaimValue
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetClaimValueTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class SetClaimValueTest extends WikibaseApiTestCase {

	/**
	 * @var ValueFormatter
	 */
	private $entityIdFormatter = null;

	/**
	 * @var ValueFormatter
	 */
	private $propertyValueFormatter = null;

	/**
	 * @param Entity $entity
	 * @param EntityId $propertyId
	 *
	 * @return Entity
	 */
	protected function addClaimsAndSave( Entity $entity, EntityId $propertyId ) {
		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->newFromEntity( $entity );
		$content->save( '', null, EDIT_NEW );

		$claim = $entity->newClaim( new \Wikibase\PropertyValueSnak( $propertyId, new \DataValues\StringValue( 'o_O' ) ) );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P' );
		$entity->addClaim( $claim );

		$content->save( '' );

		return $content->getEntity();
	}

	/**
	 * @param EntityId $propertyId
	 *
	 * @return Entity[]
	 */
	protected function getEntities( EntityId $propertyId ) {
		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );

		$item = Item::newEmpty();

		return array(
			$this->addClaimsAndSave( $item, $propertyId ),
			$this->addClaimsAndSave( $property, $propertyId ),
		);
	}

	public function testValidRequests() {
		$argLists = array();

		$property = Property::newFromType( 'commonsMedia' );
		$content = new PropertyContent( $property );
		$content->save( '', null, EDIT_NEW );
		$property = $content->getEntity();

		foreach( $this->getEntities( $property->getId() ) as $entity ) {
			/**
			 * @var Claim $claim
			 */
			foreach ( $entity->getClaims() as $claim ) {
				$value = new StringValue( 'Kittens.png' );
				$argLists[] = array( $entity, $claim->getGuid(), $value->getArrayValue(), $this->getExpectedSummary( $claim, $value ) );
			}
		}

		foreach ( $argLists as $argList ) {
			call_user_func_array( array( $this, 'doTestValidRequest' ), $argList );
		}
	}

	public function doTestValidRequest( Entity $entity, $claimGuid, $value, $summary ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$content = $entityContentFactory->getFromId( $entity->getId() );
		$claimCount = count( $content->getEntity()->getClaims() );

		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'value' => \FormatJson::encode( $value ),
			'snaktype' => 'value',
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];

		$this->assertEquals( $value, $claim['mainsnak']['datavalue']['value'] );

		$content = $entityContentFactory->getFromId( $entity->getId() );
		$obtainedEntity = $content->getEntity();
		$generatedSummary = $content->getWikiPage()->getRevision()->getComment( Revision::RAW );

		$this->assertEquals( $summary, $generatedSummary, 'Summary mismatch' );

		$claims = new \Wikibase\Claims( $obtainedEntity->getClaims() );

		$this->assertEquals( $claimCount, $claims->count(), 'Claim count should not change after doing a setclaimvalue request' );

		$this->assertTrue( $claims->hasClaimWithGuid( $claimGuid ) );

		$dataValue = \DataValues\DataValueFactory::singleton()->newFromArray( $claim['mainsnak']['datavalue'] );

		$this->assertTrue( $claims->getClaimWithGuid( $claimGuid )->getMainSnak()->getDataValue()->equals( $dataValue ) );
	}

	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testInvalidClaimGuid( $claimGuid ) {
		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'snaktype' => 'value',
			'value' => 'abc',
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not raise an error' );
		} catch ( \UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(),  'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' ),
			array( 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f' )
		);
	}

	private function getExpectedSummary( Claim $oldClaim, DataValue $value = null ) {
		$oldSnak = $oldClaim->getMainSnak();
		$property = $this->getEntityIdFormatter()->format( $oldSnak->getPropertyId() );

		//NOTE: new snak is always a PropertyValueSnak

		if ( $value === null ) {
			$value = $oldSnak->getDataValue();
		}

		$value = $this->getPropertyValueFormatter()->format( $value );
		return '/* wbsetclaimvalue:1| */ ' . $property . ': ' . $value;
	}

	/**
	 * Returns an EntityIdFormatter like the one that should be used internally for generating
	 * summaries.
	 *
	 * @return ValueFormatter
	 */
	protected function getEntityIdFormatter() {
		if ( !$this->entityIdFormatter ) {
			$options = new FormatterOptions();

			$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
			$this->entityIdFormatter = new EntityIdLinkFormatter( $options, $titleLookup );
		}

		return $this->entityIdFormatter;
	}

	/**
	 * Returns a ValueFormatter like the one that should be used internally for generating
	 * summaries.
	 *
	 * @return ValueFormatter
	 */
	protected function getPropertyValueFormatter() {
		if ( !$this->propertyValueFormatter ) {
			$idFormatter = $this->getEntityIdFormatter();

			$options = new FormatterOptions();
			$options->setOption( 'formatter-builders-text/plain', array(
				'VT:wikibase-entityid' => function() use ( $idFormatter ) {
					return $idFormatter;
				}
			) );

			$factory = WikibaseRepo::getDefaultInstance()->getValueFormatterFactory();
			$this->propertyValueFormatter = $factory->getValueFormatter( SnakFormatter::FORMAT_PLAIN, $options );
		}

		return $this->propertyValueFormatter;
	}

}
