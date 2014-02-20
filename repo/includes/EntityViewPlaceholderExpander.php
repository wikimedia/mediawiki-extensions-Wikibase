<?php

namespace Wikibase;

use Language;
use MWException;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;

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

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var Title
	 */
	protected $targetPage;

	/**
	 * The current user language
	 *
	 * @var Language
	 */
	protected $userLanguage;

	/**
	 * @var string[]|null
	 */
	protected $extraLanguages = null;

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var UserLanguageLookup
	 */
	protected $userLanguageLookup;

	/**
	 * @param Title $targetPage the page for which this expander is supposed to handle expansion.
	 * @param User $user the current user
	 * @param Language $uiLanguage the user's current UI language (as per the present request)
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
			wfWarn( "Expansion of $name failed: " . $ex );
		} catch ( RuntimeException $ex ) {
			wfWarn( "Expansion of $name failed: " . $ex );
		}

		return false;
	}

	/**
	 * Returns an argument from a list, first checking whether it is present and has the correct type.
	 *
	 * @param array $args the argument list
	 * @param int $index the index of the desired argument
	 * @param string $type the desired type of the argument
	 * @param string $message the message to use if the argument is missing or has the wrong type
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException If the argument is missing or has the wrong type
	 */
	protected function extractArgument( $args, $index, $type, $message ) {
		// this should be the entity id, as per the call to $injector->newMarker() in getInnerHtml
		if ( !isset( $args[$index] ) || gettype( $args[$index] ) !== $type ) {
			throw new \InvalidArgumentException( $message );
		}

		return $args[$index];
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
				$entityId = $this->extractArgument(
					$args,
					0,
					'string',
					'The first argument must be an entity ID encoded as a string' );

				$entityId = $this->idParser->parse( $entityId );
				return $this->renderTermBox( $entityId );

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
	 *
	 * @throws \InvalidArgumentException
	 * @return string HTML
	 */
	public function renderTermBox( EntityId $entityId ) {
		$languages = $this->getExtraUserLanguages();

		if ( !$languages ) {
			return '';
		}

		// we may want to cache this...
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( !$entity ) {
			return '';
		}

		$termBoxView = new TermBoxView( $this->uiLanguage );
		$html = $termBoxView->renderTermBox( $this->targetPage, $entity, $languages );

		return $html;
	}

}
