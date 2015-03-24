<?php

namespace Wikibase\Test;

use ValueValidators\Result;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Validators\TermValidatorFactory;
use Wikibase\Validators\UniquenessViolation;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
class SpecialSetLabelDescriptionAliasesTest extends SpecialWikibaseRepoPageTestBase {

	protected $languageCodes = array( 'en', 'de', 'de-ch', 'ii', 'zh' );

	/**
	 * @see SpecialPageTestBase::newSpecialPage()
	 *
	 * @return SpecialSetLabelDescriptionAliases
	 */
	protected function newSpecialPage() {
		$page = new SpecialSetLabelDescriptionAliases();

		$page->setServices(
			$this->getSummaryFormatter(),
			$this->getEntityRevisionLookup(),
			$this->getEntityTitleLookup(),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getSiteStore(),
			$this->getFingerprintChangeOpsFactory(),
			$this->getContentLanguages()
		);

		return $page;
	}

	/**
	 * @return FingerprintChangeOpFactory
	 */
	private function getFingerprintChangeOpsFactory() {
		$maxLength = 32;

		return new FingerprintChangeOpFactory(
			new TermValidatorFactory(
				$maxLength,
				$this->languageCodes,
				$this->getIdParser(),
				$this->getLabelDescriptionDuplicateDetector(),
				$this->mockRepository
			)
		);
	}

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getLabelDescriptionDuplicateDetector() {
		$detector = $this->getMockBuilder( 'Wikibase\LabelDescriptionDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$self = $this; // yay PHP 5.3
		$detector->expects( $this->any() )
			->method( 'detectTermConflicts' )
			->will( $this->returnCallback( function(
				$entityType,
				array $labels,
				array $descriptions = null,
				EntityId $ignoreEntityId = null
			) use ( $self ) {
				$errors = array();

				$errors = array_merge( $errors, $self->detectDupes( $labels ) );
				$errors = array_merge( $errors, $self->detectDupes( $descriptions ) );

				$result = empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
				return $result;
			} ) );

		return $detector;
	}

	/**
	 * Mock duplicate detection: the term "DUPE" is considered a duplicate.
	 *
	 * @param string[] $terms
	 *
	 * @return array
	 */
	public function detectDupes( $terms ) {
		$errors = array();

		foreach ( $terms as $languageCode => $term ) {
			if ( $term === 'DUPE' ) {
				$q666 = new ItemId( 'Q666' );

				$errors[] =  new UniquenessViolation(
					$q666,
					'found conflicting terms',
					'test-conflict',
					array(
						$term,
						$languageCode,
						$q666,
					)
				);
			}
		}

		return $errors;
	}

	/**
	 * @return ContentLanguages
	 */
	private function getContentLanguages() {
		$languages = $this->getMock( 'Wikibase\Lib\ContentLanguages' );

		$languages->expects( $this->any() )
			->method( 'getLanguages' )
			->will( $this->returnValue( $this->languageCodes ) );

		$languageCodes = $this->languageCodes; // for PHP 5.3
		$languages->expects( $this->any() )
			->method( 'hasLanguage' )
			->will( $this->returnCallback( function( $code ) use ( $languageCodes ) {
				return in_array( $code, $languageCodes );
			} ) );

		return $languages;
	}

	/**
	 * @return string
	 */
	private function createNewItem() {
		$item = Item::newEmpty();
		// add data and check if it is shown in the form
		$item->setLabel( 'de', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setAliases( 'de', array( 'foo' ) );

		// save the item
		$this->mockRepository->putEntity( $item );

		// return the id
		return $item->getId()->getSerialization();
	}

	public function testExecute() {
		$id = $this->createNewItem();

		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array( 'edit' => true ) ) );

		$this->newSpecialPage();

		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			),
		);
		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-language',
				'class' => 'wb-input',
				'name' => 'language',
				'value' => 'en',
			),
		);
		$matchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-label',
				'class' => 'wb-input',
				'name' => 'label',
			),
		);
		$matchers['description'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-description',
				'class' => 'wb-input',
				'name' => 'description',
			),
		);
		$matchers['aliases'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-aliases',
				'class' => 'wb-input',
				'name' => 'aliases',
			),
		);
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setlabeldescriptionaliases-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setlabeldescriptionaliases-submit',
			),
		);

		// execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '', null, 'en' );
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( $id, null, 'en' );
		$matchers['id']['attributes'] = array(
			'type' => 'hidden',
			'name' => 'id',
			'value' => $id,
		);
		$matchers['language']['attributes'] = array(
			'type' => 'hidden',
			'name' => 'language',
			'value' => 'en',
		);

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $id . '/de', null, 'en' );
		$matchers['language']['attributes']['value'] = 'de';
		$matchers['value']['attributes']['value'] = 'foo';

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}

}
