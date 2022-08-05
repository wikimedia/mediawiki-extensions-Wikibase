<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use HashSiteStore;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	use AliasChangeOpDeserializationTester;
	use ClaimsChangeOpDeserializationTester;
	use DescriptionsChangeOpDeserializationTester;
	use LabelsChangeOpDeserializationTester;

	public function getEntity() {
		return new Property( new NumericPropertyId( 'P100' ), null, 'foo' );
	}

	public function getChangeOpDeserializer() {
		$changeOpFactoryProvider = WikibaseRepo::getChangeOpFactoryProvider();

		return new PropertyChangeOpDeserializer( new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en' ] ) ),
			new SiteLinkBadgeChangeOpSerializationValidator(
				WikibaseRepo::getEntityTitleLookup(),
				[]
			),
			WikibaseRepo::getExternalFormatStatementDeserializer(),
			new SiteLinkPageNormalizer( [] ),
			new SiteLinkTargetProvider( new HashSiteStore() ),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getEntityLookup(),
			WikibaseRepo::getStringNormalizer(),
			[]
		) );
	}

	public function testGivenAllPropertyFieldsInChangeRequest_changeOpChangesAllFieldsOnceApplied() {
		$property = $this->getEntity();

		$newAlias = 'test-alias';
		$newLabel = 'test-label';
		$newDescription = 'test-description';

		$otherProperty = new NumericPropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $otherProperty ) );
		$statementSerializer = WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementSerializer();
		$statementSerialization = $statementSerializer->serialize( $statement );

		$changeRequest = [
			'aliases' => [ 'en' => [ 'language' => 'en', 'value' => $newAlias ] ],
			'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ],
			'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ],
			'claims' => [ $statementSerialization ],
		];

		$deserializer = $this->getChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp( $changeRequest );

		$changeOp->apply( $property, new Summary() );

		$this->assertSame( [ $newAlias ], $property->getAliasGroups()->getByLanguage( 'en' )->getAliases() );
		$this->assertSame( $newLabel, $property->getLabels()->getByLanguage( 'en' )->getText() );
		$this->assertSame( $newDescription, $property->getDescriptions()->getByLanguage( 'en' )->getText() );

		$this->assertFalse( $property->getStatements()->getByPropertyId( $otherProperty )->isEmpty() );
	}

	public function testGivenSitelinkFieldInChangeRequest_createEntityChangeOpThrowsException() {
		$deserializer = $this->getChangeOpDeserializer();

		$this->expectException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'sitelinks' => [ 'site-link-change-data' ] ] );
	}

}
