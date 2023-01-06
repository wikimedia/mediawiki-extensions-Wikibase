<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Logger\LoggerFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Summary;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\SummaryFormatter;

/**
 * Special page for setting the aliases of a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetAliases extends SpecialModifyTerm {

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
			'SetAliases',
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

		return $this->getBaseRevision()->getEntity() instanceof AliasesProvider;
	}

	/**
	 * @see SpecialModifyTerm::getPostedValue()
	 *
	 * @return string|null
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'aliases' );
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
		if ( !( $entity instanceof AliasesProvider ) ) {
			throw new InvalidArgumentException( '$entity must be an AliasesProvider' );
		}
		$aliases = $entity->getAliasGroups();
		if ( $aliases->hasGroupForLanguage( $languageCode ) ) {
			return implode( '|', $aliases->getByLanguage( $languageCode )->getAliases() );
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
	 * @throws UserInputException|InvalidArgumentException
	 * @return Summary
	 */
	protected function setValue( EntityDocument $entity, $languageCode, $value ) {
		if ( !( $entity instanceof AliasesProvider ) ) {
			throw new InvalidArgumentException( '$entity must be an AliasesProvider' );
		}

		$summary = new Summary( 'wbsetaliases' );
		if ( $value === '' ) {
			$aliases = $entity->getAliasGroups()->getByLanguage( $languageCode )->getAliases();
			$changeOp = $this->termChangeOpFactory->newRemoveAliasesOp( $languageCode, $aliases );
		} else {
			$this->assertNoPipeCharacterInAliases( $entity, $languageCode );
			$changeOp = $this->termChangeOpFactory->newSetAliasesOp( $languageCode, explode( '|', $value ) );
		}

		$fingerprintChangeOp = $this->termChangeOpFactory->newFingerprintChangeOp( new ChangeOps( [ $changeOp ] ) );

		$this->applyChangeOp( $fingerprintChangeOp, $entity, $summary );

		return $summary;
	}

	/**
	 * Screams and throws an error if any of existing aliases has pipe character
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 *
	 * @throws UserInputException
	 * @suppress PhanTypeMismatchDeclaredParam Intersection type
	 */
	private function assertNoPipeCharacterInAliases( AliasesProvider $entity, $languageCode ) {
		$aliases = $entity->getAliasGroups();
		if ( !$aliases->hasGroupForLanguage( $languageCode ) ) {
			return;
		}
		$aliasesInLang = $entity->getAliasGroups()->getByLanguage( $languageCode )->getAliases();

		foreach ( $aliasesInLang as $alias ) {
			if ( strpos( $alias, '|' ) !== false ) {
				$logger = LoggerFactory::getInstance( 'Wikibase' );
				$logger->error( 'Special:SetAliases attempt to save pipes in aliases' );
				throw new UserInputException(
					'wikibase-wikibaserepopage-pipe-in-alias',
					[],
					$this->msg( 'wikibase-wikibaserepopage-pipe-in-alias' )->text()
				);
			}
		}
	}

}
