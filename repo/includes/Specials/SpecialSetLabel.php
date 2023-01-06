<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\SummaryFormatter;

/**
 * Special page for setting the label of a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetLabel extends SpecialModifyTerm {

	public function __construct(
		array $tags,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityPermissionChecker $entityPermissionChecker,
		ContentLanguages $termsLanguages,
		LanguageNameUtils $languageNameUtils
	) {
		parent::__construct(
			'SetLabel',
			$tags,
			$changeOpFactoryProvider,
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory,
			$entityPermissionChecker,
			$termsLanguages,
			$languageNameUtils
		);
	}

	public static function factory(
		LanguageNameUtils $languageNameUtils,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		MediawikiEditEntityFactory $editEntityFactory,
		EntityPermissionChecker $entityPermissionChecker,
		EntityTitleLookup $entityTitleLookup,
		SettingsArray $repoSettings,
		SummaryFormatter $summaryFormatter,
		ContentLanguages $termsLanguages
	): self {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$repoSettings->getSetting( 'dataRightsUrl' ),
			$repoSettings->getSetting( 'dataRightsText' )
		);

		return new self(
			$repoSettings->getSetting( 'specialPageTags' ),
			$changeOpFactoryProvider,
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory,
			$entityPermissionChecker,
			$termsLanguages,
			$languageNameUtils
		);
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyTerm::validateInput
	 *
	 * @return bool
	 */
	protected function validateInput() {
		if ( !parent::validateInput() ) {
			return false;
		}

		return $this->getBaseRevision()->getEntity() instanceof LabelsProvider;
	}

	/**
	 * @see SpecialModifyTerm::getPostedValue()
	 *
	 * @return string|null
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'label' );
	}

	/**
	 * @see SpecialModifyTerm::getValue()
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getValue( EntityDocument $entity, $languageCode ) {
		if ( !( $entity instanceof LabelsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a LabelsProvider' );
		}

		$labels = $entity->getLabels();

		if ( $labels->hasTermForLanguage( $languageCode ) ) {
			return $labels->getByLanguage( $languageCode )->getText();
		}

		return '';
	}

	/**
	 * @see SpecialModifyTerm::setValue()
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( EntityDocument $entity, $languageCode, $value ) {
		$value = $value === '' ? null : $value;
		$summary = new Summary( 'wbsetlabel' );

		if ( $value === null ) {
			$changeOp = $this->termChangeOpFactory->newRemoveLabelOp( $languageCode );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetLabelOp( $languageCode, $value );
		}

		$fingerprintChangeOp = $this->termChangeOpFactory->newFingerprintChangeOp( new ChangeOps( [ $changeOp ] ) );

		$this->applyChangeOp( $fingerprintChangeOp, $entity, $summary );

		return $summary;
	}

}
