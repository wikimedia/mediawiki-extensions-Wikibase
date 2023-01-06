<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
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
 * Special page for setting the description of a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetDescription extends SpecialModifyTerm {

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
			'SetDescription',
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

		return $this->getBaseRevision()->getEntity() instanceof DescriptionsProvider;
	}

	/**
	 * @see SpecialModifyTerm::getPostedValue()
	 *
	 * @return string|null
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'description' );
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
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a DescriptionsProvider' );
		}

		$descriptions = $entity->getDescriptions();

		if ( $descriptions->hasTermForLanguage( $languageCode ) ) {
			return $descriptions->getByLanguage( $languageCode )->getText();
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
		$summary = new Summary( 'wbsetdescription' );

		if ( $value === null ) {
			$changeOp = $this->termChangeOpFactory->newRemoveDescriptionOp( $languageCode );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetDescriptionOp( $languageCode, $value );
		}

		$fingerprintChangeOp = $this->termChangeOpFactory->newFingerprintChangeOp( new ChangeOps( [ $changeOp ] ) );

		$this->applyChangeOp( $fingerprintChangeOp, $entity, $summary );

		return $summary;
	}

}
