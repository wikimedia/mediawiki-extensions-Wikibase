<?php

namespace Wikibase\Test;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Serializers\DispatchingEntitySerializer;
use Wikibase\Lib\Serializers\LibSerializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Lib\Serializers\DispatchingEntitySerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingEntitySerializerTest extends EntitySerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getInstance
	 *
	 * @return DispatchingEntitySerializer
	 */
	protected function getInstance() {
		return new DispatchingEntitySerializer( new LibSerializerFactory() );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @return array
	 */
	public function validProvider() {
		return array(
			array( $this->getItemInstance() ),
			array( $this->getPropertyInstance() ),
		);
	}

	/**
	 * @return Entity
	 */
	protected function getEntityInstance() {
		return $this->getInstance();
	}

	/**
	 * @return Entity
	 */
	protected function getItemInstance() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'test', 'Foo' );

		return $item;
	}

	/**
	 * @return Entity
	 */
	protected function getPropertyInstance() {
		$property = Property::newFromType( 'wibbly' );
		$property->setId( new PropertyId( 'P17' ) );

		return $property;
	}

	public function testDataModelCompatability() {
		$serializer = $this->getInstance();
		$dataModelSerializerFactory = new SerializerFactory( new DataValueSerializer() );
		$dataModelSerializer = $dataModelSerializerFactory->newEntitySerializer();

		$qualifierSnakList = new SnakList();
		$qualifierSnakList->addSnak( new PropertyValueSnak( new PropertyId( 'P100' ), new StringValue( 'QualSnak1' ) ) );
		$qualifierSnakList->addSnak( new PropertyValueSnak( new PropertyId( 'P100' ), new StringValue( 'QualSnak2' ) ) );
		$qualifierSnakList->addSnak( new PropertyValueSnak( new PropertyId( 'P300' ), new StringValue( 'QualSnak3' ) ) );
		$item = new Item(
			new ItemId( 'Q666' ),
			new Fingerprint(
				new TermList(
					array(
						new Term( 'en', 'EnLabel' ),
						new Term( 'pt', 'PtLabel' ),
					)
				),
				new TermList(
					array(
						new Term( 'de', 'German Description' ),
					)
				),
				new AliasGroupList(
					array(
						new AliasGroup(
							'fr',
							array(
								'alias1',
								'alias2',
							)
						),
						new AliasGroup(
							'pl',
							array(
								'alias1-pl',
							)
						)
					)
				)
			),
			new SiteLinkList(
				array(
					new SiteLink( 'enwiki', 'SomePage', array(
						new ItemId( 'Q8' ),
						new ItemId( 'Q9' ),
					) ),
					new SiteLink( 'dewiki', 'GermanPage' ),
				)
			),
			new StatementList(
				array(
					new Statement(
						new PropertyValueSnak( new PropertyId( 'P200' ), new StringValue( 'SomeString' ) ),
						null,
						null,
						'Q200$11111111-1111-1111-1111-11111111111A'
					),
					new Statement(
						new PropertyValueSnak( new PropertyId( 'P100' ), new StringValue( 'SomeString' ) ),
						null,
						null,
						'Q100$11111111-1111-1111-1111-11111111111B'
					),
					new Statement(
						new PropertyValueSnak( new PropertyId( 'P100' ), new StringValue( 'SomeString' ) ),
						$qualifierSnakList,
						new ReferenceList(
							array(
								new Reference(
									array(
										new PropertyValueSnak( new PropertyId( 'P100' ), new StringValue( 'RefSank' ) ),
										new PropertySomeValueSnak( new PropertyId( 'P100' ) ),
										new PropertyNoValueSnak( new PropertyId( 'P101' ) ),
									)
								)
							)
						),
						'Q100$11111111-1111-1111-1111-11111111111C'
					),
				)
			)
		);

		$libResult = $serializer->getSerialized( $item );
		$dmResult = $dataModelSerializer->serialize( $item );

		$this->assertEquals( $dmResult, $libResult );
	}

}
