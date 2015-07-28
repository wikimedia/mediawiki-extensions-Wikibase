<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ClaimSerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
class ClaimSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getInstance
	 *
	 * @return ClaimSerializer
	 */
	protected function getInstance() {
		return new ClaimSerializer( new SnakSerializer() );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$id = new PropertyId( 'P42' );

		$validArgs[] = new Statement( new PropertyNoValueSnak( $id ) );

		$validArgs[] = new Statement( new PropertySomeValueSnak( $id ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$statement = new Statement( new PropertyNoValueSnak( $id ) );

		$validArgs['statement'] = array(
			$statement,
			array(
				'id' => $statement->getGuid(),
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42',
				),
				'rank' => 'normal',
				'type' => 'statement',
			),
		);

		$claim = new Statement(
			new PropertyNoValueSnak( $id ),
			new SnakList( array(
				new PropertyNoValueSnak( $id ),
				new PropertySomeValueSnak( $id ),
				new PropertyNoValueSnak(
					new PropertyId( 'P1' )
				),
			) )
		);

		$optsWithHash = new SerializationOptions();
		$optsWithHash->setOption( SerializationOptions::OPT_SERIALIZE_SNAKS_WITH_HASH, true );
		$qualifierSerializer = new SnakSerializer( null, $optsWithHash );
		$snakSerializer = new SnakSerializer();

		$validArgs['complexClaimByProp'] = array(
			$claim,
			array(
				'id' => $claim->getGuid(),
				'mainsnak' => $snakSerializer->getSerialized( new PropertyNoValueSnak( $id ) ),
				'qualifiers' => array(
					'P42' => array(
						$qualifierSerializer->getSerialized( new PropertyNoValueSnak( $id ) ),
						$qualifierSerializer->getSerialized( new PropertySomeValueSnak( $id ) ),
					),
					'P1' => array(
						$qualifierSerializer->getSerialized( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ),
					),
				),
				'qualifiers-order' => array( 'P42', 'P1' ),
				'type' => 'statement',
				'rank' => 'normal',
			),
		);

		$opts = new SerializationOptions();
		$opts->setOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES, array() );

		$validArgs['complexClaimList'] = array(
			$claim,
			array(
				'id' => $claim->getGuid(),
				'mainsnak' => $snakSerializer->getSerialized( new PropertyNoValueSnak( $id ) ),
				'qualifiers' => array(
					$qualifierSerializer->getSerialized( new PropertyNoValueSnak( $id ) ),
					$qualifierSerializer->getSerialized( new PropertySomeValueSnak( $id ) ),
					$qualifierSerializer->getSerialized( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ),
				),
				'qualifiers-order' => array( 'P42', 'P1' ),
				'type' => 'statement',
				'rank' => 'normal',
			),
			$opts
		);

		return $validArgs;
	}

}
