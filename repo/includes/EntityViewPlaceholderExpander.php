<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;
use MWException;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityLookup;

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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpander {

	private $user;
	private $targetPage;
	private $extraLanguages = null;
	private $idParser;
	private $entityLookup;
	private $userLanguageLookup;

	/**
	 * @param Title $targetPage the page for which this expander is supposed to handle expansion.
	 * @param User $user the current user
	 * @param Language $uiLanguage the user's current UI language (as per the present request)
	 *
	 * @param EntityIdParser $idParser
	 * @param EntityLookup $entityLookup
	 * @param UserLanguageLookup $userLanguageLookup
	 */
	public function __construct(
		Title $targetPage,
		User $user,
		Language $uiLanguage,
		EntityIdParser $idParser,
		EntityLookup $entityLookup,
		UserLanguageLookup $userLanguageLookup
	) {
		$this->targetPage = $targetPage;
		$this->user = $user;
		$this->uiLanguage = $uiLanguage;
		$this->idParser = $idParser;
		$this->entityLookup = $entityLookup;
		$this->userLanguageLookup = $userLanguageLookup;
	}

	/**
	 * Returns a list of languages desired by the user in addition to the current interface language.
	 *
	 * @see UserLanguages
	 *
	 * @return string[]
	 */
	public function getExtraUserLanguages() {
		if ( $this->extraLanguages === null ) {
			if ( $this->user->isAnon() ) {
				// no extra languages for anon user
				$this->extraLanguages = array();
			} else {
				// ignore current interface language
				$skip = array( $this->uiLanguage->getCode() );
				$this->extraLanguages = $this->userLanguageLookup->getExtraUserLanguages(
					$this->user, $skip );
			}
		}

		return $this->extraLanguages;
	}

	/**
	 * Callback for expanding placeholders to HTML,
	 * for use as a callback passed to with TextInjector::inject().
	 *
	 * @note This delegates to expandPlaceholder, which encapsulates knowledge about
	 * the meaning of each placeholder name, as used by EntityView.
	 *
	 * @param string $name the name (or kind) of placeholder; determines how the expansion is done.
	 * @param mixed ... additional arguments associated with the placeholder
	 *
	 * @return string HTML to be substituted for the placeholder in the output.
	 */
	public function getHtmlForPlaceholder( $name ) {
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

		return $this->idParser->parse( $entityId );
	}

	/**
	 * Dispatch the expansion of placeholders based on the name.
	 *
	 * @note This encodes knowledge about which placeholders are used by EntityView with what
	 *       intended meaning.
	 *
	 * @param $name
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
					isset( $args[1] ) ? intval( $args[1] ) : 0
				);

			case 'termbox-toc':
				return $this->renderTermBoxTocEntry();

			default:
				wfWarn( "Unknown placeholder: $name" );
				return '(((' . htmlspecialchars( $name ) . ')))';
		}
	}

	/**
	 * Generates HTML to be injected into the TOC as a link to the term box.
	 *
	 * @return string HTML
	 */
	public function renderTermBoxTocEntry() {
		$languages = $this->getExtraUserLanguages();

		if ( !$languages ) {
			return '';
		}

		$html = wfTemplate( 'wb-entity-toc-section',
			0, // section number, not really used, it seems
			'wb-terms',
			wfMessage( 'wikibase-terms' )->inLanguage( $this->uiLanguage )->text()
		);

		return $html;
	}

	/**
	 * Generates HTML of the term box, to be injected into the entity page.
	 *
	 * @param Entityid $entityId
	 * @param int $entityRevision
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function renderTermBox( EntityId $entityId, $entityRevision ) {
		$languages = $this->getExtraUserLanguages();

		if ( !$languages ) {
			return '';
		}

		// we may want to cache this...
		$entity = $this->entityLookup->getEntity( $entityId, $entityRevision );

		if ( !$entity ) {
			return '';
		}

		$termBoxView = new TermBoxView( $this->uiLanguage );
		$html = $termBoxView->renderTermBox( $this->targetPage, $entity, $languages );

		return $html;
	}

}
