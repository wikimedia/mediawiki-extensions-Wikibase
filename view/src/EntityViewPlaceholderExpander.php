<?php

namespace Wikibase\View;

use InvalidArgumentException;
use MWException;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\View\Template\TemplateFactory;

/**
 * Utility for expanding the placeholders left in the HTML by EntityView.
 *
 * This is used to inject any non-cacheable information into the HTML
 * that was cached as part of the ParserOutput.
 *
 * @note This class encapsulated knowledge about which placeholders are used by
 * EntityView, and with what meaning.
 *
 * @see EntityView
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpander {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var Title
	 */
	private $targetPage;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var string
	 */
	private $uiLanguageCode;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var string[]
	 */
	private $termsLanguages;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param Title $targetPage the page for which this expander is supposed to handle expansion.
	 * @param User $user the current user
	 * @param string $uiLanguageCode the user's current UI language (as per the present request)
	 * @param EntityIdParser $entityIdParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param string[] $termsLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		Title $targetPage,
		User $user,
		$uiLanguageCode,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		array $termsLanguages,
		LanguageNameLookup $languageNameLookup
	) {
		$this->targetPage = $targetPage;
		$this->user = $user;
		$this->uiLanguageCode = $uiLanguageCode;
		$this->entityIdParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->templateFactory = $templateFactory;
		$this->termsLanguages = $termsLanguages;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * Callback for expanding placeholders to HTML,
	 * for use as a callback passed to with TextInjector::inject().
	 *
	 * @note This delegates to expandPlaceholder, which encapsulates knowledge about
	 * the meaning of each placeholder name, as used by EntityView.
	 *
	 * @param string $name the name (or kind) of placeholder; determines how the expansion is done.
	 * @param mixed [$arg,...] Additional arguments associated with the placeholder.
	 *
	 * @return string HTML to be substituted for the placeholder in the output.
	 */
	public function getHtmlForPlaceholder( $name /*...*/ ) {
		$args = func_get_args();
		$name = array_shift( $args );

		try {
			$html = $this->expandPlaceholder( $name, $args );
			return $html;
		} catch ( MWException $ex ) {
			wfWarn( "Expansion of $name failed: " . $ex->getMessage() );
		} catch ( RuntimeException $ex ) {
			wfWarn( "Expansion of $name failed: " . $ex->getMessage() );
		}

		return false;
	}

	/**
	 * Gets an EntityId object from a string (with error handling)
	 *
	 * @param string $entityId
	 *
	 * @return EntityId
	 * @throws InvalidArgumentException
	 */
	private function getEntityIdFromString( $entityId ) {
		if ( !is_string( $entityId ) ) {
			throw new InvalidArgumentException(
				'The first argument must be an entity ID encoded as a string'
			);
		}

		return $this->entityIdParser->parse( $entityId );
	}

	/**
	 * Dispatch the expansion of placeholders based on the name.
	 *
	 * @note This encodes knowledge about which placeholders are used by EntityView with what
	 *       intended meaning.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @return string
	 */
	protected function expandPlaceholder( $name, array $args ) {
		switch ( $name ) {
			case 'termbox':
				$entityId = $this->getEntityIdFromString( $args[0] );
				return $this->renderTermBox(
					$entityId,
					isset( $args[1] ) ? (int)$args[1] : 0
				);
			case 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class':
				return $this->isInitiallyCollapsed() ? 'wikibase-initially-collapsed' : '';
			default:
				wfWarn( "Unknown placeholder: $name" );
				return '(((' . htmlspecialchars( $name ) . ')))';
		}
	}

	/**
	 * @return bool If the terms list should be initially collapsed for the current user.
	 */
	 private function isInitiallyCollapsed() {
		if ( $this->user->isAnon() ) {
			return isset( $_COOKIE['wikibase-entitytermsview-showEntitytermslistview'] )
				&& $_COOKIE['wikibase-entitytermsview-showEntitytermslistview'] === 'false';
		} else {
			return !$this->user->getBoolOption( 'wikibase-entitytermsview-showEntitytermslistview' );
		}
	 }

	/**
	 * Generates HTML of the term box, to be injected into the entity page.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function renderTermBox( EntityId $entityId, $revisionId ) {
		try {
			// we may want to cache this...
			$entityRev = $this->entityRevisionLookup->getEntityRevision( $entityId, $revisionId );
		} catch ( StorageException $ex ) {
			// Could not load entity revision, $revisionId might be a deleted revision
			return '';
		}

		if ( !$entityRev ) {
			// Could not load entity revision, entity might not exist for $entityId.
			return '';
		}

		$entity = $entityRev->getEntity();

		$entityTermsView = new EntityTermsView(
			$this->templateFactory,
			null,
			$this->languageNameLookup,
			$this->uiLanguageCode
		);

		// FIXME: assumes all entities have a fingerprint
		$html = $entityTermsView->getEntityTermsForLanguageListView(
			$entity->getFingerprint(),
			$this->termsLanguages,
			$this->targetPage
		);

		return $html;
	}

}
