<?php

namespace Wikibase;

use Html;
use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\TextInjector;

/**
 * Base class for creating views for all different kinds of Wikibase\Entity.
 * For the Wikibase\Entity this basically is what the Parser is for WikitextContent.
 *
 * @todo  We might want to re-design this at a later point, designing this as a more generic and encapsulated rendering
 *        of DataValue instances instead of having functions here for generating different parts of the HTML. Right now
 *        these functions require an EntityRevision while a DataValue (if it were implemented) should be sufficient.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class EntityView {

	/**
	 * @var FingerprintView
	 */
	protected $fingerprintView;

	/**
	 * @var ClaimsView
	 */
	protected $claimsView;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var TextInjector
	 */
	protected $textInjector;

	public function __construct(
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		Language $language
	) {
		// @todo: move the $editable flag here, instead of passing it around everywhere
		$this->fingerprintView = $fingerprintView;
		$this->claimsView = $claimsView;
		$this->language = $language;

		$this->textInjector = new TextInjector();
	}

	/**
	 * Returns the placeholder map build while generating HTML.
	 * The map returned here may be used with TextInjector.
	 *
	 * @return array[] string -> array
	 */
	public function getPlaceholders() {
		return $this->textInjector->getMarkers();
	}

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @note: The HTML returned by this method may contain placeholders. Such placeholders can be
	 * expanded with the help of TextInjector::inject() calling back to
	 * EntityViewPlaceholderExpander::getExtraUserLanguages()
	 * @note: In order to keep the list of placeholders small, this calls resetPlaceholders().
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string HTML
	 */
	public function getHtml( EntityRevision $entityRevision, $editable = true ) {
		$entity = $entityRevision->getEntity();

		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.

		$entityId = $entity->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes

		$html = wfTemplate( 'wikibase-entityview',
			$entity->getType(),
			$entityId,
			$this->language->getCode(),
			$this->language->getDir(),
			$this->getInnerHtml( $entityRevision, $editable )
		);

		if ( $editable ) {
			$html .= $this->getLoadingSpinnerInlineScript();
		}

		return $html;
	}

	private function getLoadingSpinnerInlineScript() {
		// Show loading spinner as long as JavaScript is initialising.
		// The fastest way to show it is placing the script right after the corresponding HTML.
		// Remove it after a while in any case (e.g. some resources might not have been loaded
		// silently, so JavaScript is not initialising).
		// Additionally attaching to window.error would only make sense before any other
		// JavaScript is parsed.
		return Html::inlineScript( '
if ( $ ) {
	$( ".wikibase-entityview" ).addClass( "loading" ).after( function() {
		var $div = $( "<div/>" ).addClass( "wb-entity-spinner mw-small-spinner" );
		$div.css( "top", $div.height() + "px" );
		$div.css(
			"' . ( $this->language->isRTL() ? 'right' : 'left' ) . '",
			( ( $( this ).width() - $div.width() ) / 2 | 0 ) + "px"
		);
		return $div;
	} );
	window.setTimeout( function() {
		$( ".wikibase-entityview" ).removeClass( "loading" );
		$( ".wb-entity-spinner" ).remove();
	}, 7000 );
}
' );
	}

	/**
	 * Builds and returns the inner HTML for representing a whole WikibaseEntity. The difference to getHtml() is that
	 * this does not group all the HTMl within one parent node as one entity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$entity = $entityRevision->getEntity();

		$html = $this->getHtmlForFingerprint( $entity, $editable );
		$html .= $this->getHtmlForToc();
		$html .= $this->getHtmlForTermBox( $entityRevision, $editable );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the HTML for the entity's fingerprint.
	 *
	 * @param Entity $entity
	 * @param bool $editable
	 *
	 * @return string
	 */
	protected function getHtmlForFingerprint( Entity $entity, $editable = true ) {
		return $this->fingerprintView->getHtml( $entity->getFingerprint(), $entity->getId(), $editable );
	}

	/**
	 * Builds and returns the HTML for the toc.
	 *
	 * @return string
	 */
	protected function getHtmlForToc() {
		$tocSections = $this->getTocSections();

		if ( count( $tocSections ) < 2 ) {
			// Including the marker for the termbox toc entry, there is fewer
			// 3 sections. MediaWiki core doesn't show a TOC unless there are
			// at least 3 sections, so we shouldn't either.
			return '';
		}

		// Placeholder for the TOC entry for the term box (which may or may not be used for a given user).
		// EntityViewPlaceholderExpander must know about the 'termbox-toc' name.
		$tocContent = $this->textInjector->newMarker( 'termbox-toc' );

		$i = 1;

		foreach ( $tocSections as $id => $message ) {
			$tocContent .= wfTemplate( 'wb-entity-toc-section',
				$i++,
				$id,
				wfMessage( $message )->text()
			);
		}

		return wfTemplate( 'wb-entity-toc',
			wfMessage( 'toc' )->text(),
			$tocContent
		);
	}

	/**
	 * Returns the sections that should displayed in the toc.
	 *
	 * @return string[] array( link target => system message key )
	 */
	protected function getTocSections() {
		return array();
	}

	/**
	 * @param EntityRevision $entityRevision
	 *
	 * @return string
	 */
	protected function getHtmlForTermBox( EntityRevision $entityRevision ) {
		$entityId = $entityRevision->getEntity()->getId();

		if ( $entityId !== null ) {
			// Placeholder for a termbox for the present item.
			// EntityViewPlaceholderExpander must know about the parameters used here.
			return $this->textInjector->newMarker(
				'termbox',
				$entityId->getSerialization(),
				$entityRevision->getRevision()
			);
		}

		return '';
	}

}
