<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use HashSiteStore;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	use AliasChangeOpDeserializationTester;
	use ClaimsChangeOpDeserializationTester;
	use DescriptionsChangeOpDeserializationTester;
	use LabelsChangeOpDeserializationTester;

	public function getEntity() {
		return new Property( new PropertyId( 'P100' ), null, 'foo' );
	}

	public function getChangeOpDeserializer() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		return new PropertyChangeOpDeserializer( new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en' ] ) ),
			new SiteLinkBadgeChangeOpSerializationValidator(
				$wikibaseRepo->getEntityTitleLookup(),
				[]
			),
			$wikibaseRepo->getExternalFormatStatementDeserializer(),
			new SiteLinkTargetProvider( new HashSiteStore() ),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStringNormalizer(),
			[]
		) );
	}

	public function testGivenAllPropertyFieldsInChangeRequest_changeOpChangesAllFieldsOnceApplied() {
		$property = $this->getEntity();

		$newAlias = 'test-alias';
		$newLabel = 'test-label';
		$newDescription = 'test-description';

		$otherProperty = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $otherProperty ) );
		$statementSerialization = WikibaseRepo::getDefaultInstance()->getStatementSerializer()->serialize( $statement );

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

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$deserializer->createEntityChangeOp( [ 'sitelinks' => [ 'site-link-change-data' ] ] );
	}

}
