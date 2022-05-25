<?php

namespace Wikibase\Repo\Specials;

use OutputPage;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Summary;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\DataTypeSelector;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Specials\HTMLForm\HTMLAliasesField;
use Wikibase\Repo\Specials\HTMLForm\HTMLContentLanguageField;
use Wikibase\Repo\Specials\HTMLForm\HTMLTrimmedTextField;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\SummaryFormatter;

/**
 * Page for creating new Wikibase properties.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialNewProperty extends SpecialNewEntity {
	public const FIELD_LANG = 'lang';
	public const FIELD_DATATYPE = 'datatype';
	public const FIELD_LABEL = 'label';
	public const FIELD_DESCRIPTION = 'description';
	public const FIELD_ALIASES = 'aliases';

	/** @var DataTypeFactory */
	private $dataTypeFactory;

	/**
	 * @var TermsCollisionDetector
	 */
	private $termsCollisionDetector;

	public function __construct(
		array $tags,
		SpecialPageCopyrightView $specialPageCopyrightView,
		EntityNamespaceLookup $entityNamespaceLookup,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		DataTypeFactory $dataTypeFactory,
		TermsCollisionDetector $termsCollisionDetector
	) {
		parent::__construct(
			'NewProperty',
			'property-create',
			$tags,
			$specialPageCopyrightView,
			$entityNamespaceLookup,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);

		$this->dataTypeFactory = $dataTypeFactory;
		$this->termsCollisionDetector = $termsCollisionDetector;
	}

	public static function factory(
		DataTypeFactory $dataTypeFactory,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTitleLookup $entityTitleLookup,
		TermsCollisionDetector $propertyTermsCollisionDetector,
		SettingsArray $repoSettings,
		SummaryFormatter $summaryFormatter
	): self {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$repoSettings->getSetting( 'dataRightsUrl' ),
			$repoSettings->getSetting( 'dataRightsText' )
		);

		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			$copyrightView,
			$entityNamespaceLookup,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory,
			$dataTypeFactory,
			$propertyTermsCollisionDetector
		);
	}

	/**
	 * @see SpecialNewEntity::doesWrites
	 *
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialNewEntity::createEntityFromFormData
	 *
	 * @param array $formData
	 *
	 * @return Property
	 */
	protected function createEntityFromFormData( array $formData ) {
		$languageCode = $formData[ self::FIELD_LANG ];

		$property = Property::newFromType( $formData[ self::FIELD_DATATYPE ] );

		$property->setLabel( $languageCode, $formData[ self::FIELD_LABEL ] );
		$property->setDescription( $languageCode, $formData[ self::FIELD_DESCRIPTION ] );

		$property->setAliases( $languageCode, $formData[ self::FIELD_ALIASES ] );

		return $property;
	}

	private function dataTypeExists( string $dataType ): bool {
		return in_array( $dataType, $this->dataTypeFactory->getTypeIds() );
	}

	/**
	 * @see SpecialNewEntity::getFormFields()
	 *
	 * @return array[]
	 */
	protected function getFormFields() {
		$formFields = [
			self::FIELD_LANG => [
				'name' => self::FIELD_LANG,
				'class' => HTMLContentLanguageField::class,
				'id' => 'wb-newentity-language',
			],
			self::FIELD_LABEL => [
				'name' => self::FIELD_LABEL,
				'default' => $this->parts[0] ?? '',
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newentity-label',
				'placeholder-message' => 'wikibase-label-edit-placeholder',
				'label-message' => 'wikibase-newentity-label'
			],
			self::FIELD_DESCRIPTION => [
				'name' => self::FIELD_DESCRIPTION,
				'default' => $this->parts[1] ?? '',
				'class' => HTMLTrimmedTextField::class,
				'id' => 'wb-newentity-description',
				'placeholder-message' => 'wikibase-description-edit-placeholder',
				'label-message' => 'wikibase-newentity-description'
			],
			self::FIELD_ALIASES => [
				'name' => self::FIELD_ALIASES,
				'class' => HTMLAliasesField::class,
				'id' => 'wb-newentity-aliases',
			]
		];

		$selector = new DataTypeSelector(
			$this->dataTypeFactory->getTypes(),
			$this->getLanguage()->getCode()
		);

		$options = [
			$this->msg( 'wikibase-newproperty-pick-data-type' )->text() => ''
		];
		$formFields[ self::FIELD_DATATYPE ] = [
			'name' => self::FIELD_DATATYPE,
			'type' => 'select',
			'default' => $this->parts[2] ?? '',
			'options' => array_merge( $options, $selector->getOptionsArray() ),
			'id' => 'wb-newproperty-datatype',
			'validation-callback' => function ( $dataType, $formData, $form ) {
				if ( !$this->dataTypeExists( $dataType ) ) {
					return [ $this->msg( 'wikibase-newproperty-invalid-datatype' )->text() ];
				}

				return true;
			},
			'label-message' => 'wikibase-newproperty-datatype'
		];

		return $formFields;
	}

	/**
	 * @inheritDoc
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-newproperty-fieldset' );
	}

	/**
	 * @see SpecialNewEntity::getWarnings
	 *
	 * @return string[]
	 */
	protected function getWarnings() {
		if ( !$this->getUser()->isRegistered() ) {
			return [
				$this->msg(
					'wikibase-anonymouseditwarning',
					$this->msg( 'wikibase-entity-property' )
				)->parse(),
			];
		}

		return [];
	}

	/**
	 * @param array $formData
	 *
	 * @return Status
	 */
	protected function validateFormData( array $formData ) {
		if ( $formData[ self::FIELD_LABEL ] == ''
			 && $formData[ self::FIELD_DESCRIPTION ] == ''
			 && $formData[ self::FIELD_ALIASES ] === []
		) {
			return Status::newFatal( 'wikibase-newproperty-insufficient-data' );
		}

		if ( $formData[ self::FIELD_LABEL ] !== '' &&
			$formData[ self::FIELD_LABEL ] === $formData[ self::FIELD_DESCRIPTION ]
		) {
			return Status::newFatal( 'wikibase-newproperty-same-label-and-description' );
		}

		// property label uniqueness is also checked later in LabelUniquenessValidator (T289473),
		// but we repeat it here to avoid consuming a property ID if there is a collision
		$collidingPropertyId = $this->termsCollisionDetector->detectLabelCollision(
			$formData[ self::FIELD_LANG ],
			$formData[ self::FIELD_LABEL ]
		);
		if ( $collidingPropertyId !== null ) {
			return Status::newFatal(
				'wikibase-validator-label-conflict',
				$formData[ self::FIELD_LABEL ],
				$formData[ self::FIELD_LANG ],
				$collidingPropertyId
			);
		}

		return Status::newGood();
	}

	/**
	 * @param Property $property
	 *
	 * @return Summary
	 * @suppress PhanParamSignatureMismatch Uses intersection types
	 */
	protected function createSummary( EntityDocument $property ) {
		$uiLanguageCode = $this->getLanguage()->getCode();

		$summary = new Summary( 'wbeditentity', 'create' );
		$summary->setLanguage( $uiLanguageCode );
		/** @var Term|null $labelTerm */
		$labelTerm = $property->getLabels()->getIterator()->current();
		/** @var Term|null $descriptionTerm */
		$descriptionTerm = $property->getDescriptions()->getIterator()->current();
		$summary->addAutoSummaryArgs(
			$labelTerm ? $labelTerm->getText() : '',
			$descriptionTerm ? $descriptionTerm->getText() : ''
		);

		return $summary;
	}

	protected function displayBeforeForm( OutputPage $output ) {
		parent::displayBeforeForm( $output );
		$output->addModules( 'wikibase.special.languageLabelDescriptionAliases' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getEntityType() {
		return Property::ENTITY_TYPE;
	}

}
