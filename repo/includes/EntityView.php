<?php

namespace Wikibase;

use Html;
use Language;
use Wikibase\Repo\View\TextInjector;
use Wikibase\Repo\View\EntityViewPlaceholderExpander;

/**
 * Base class for creating views for all different kinds of Wikibase\Entity.
 * For the Wikibase\Entity this basically is what the Parser is for WikitextContent.
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
	 * @var Language
	 */
	protected $language;

	/**
	 * @var TextInjector
	 */
	protected $textInjector;

	public function __construct( Language $language ) {
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
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string HTML
	 */
	public function getHtml( EntityRevision $entityRevision, $editable = true ) {
		$entity = $entityRevision->getEntity();

		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.

		$entityId = $entity->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes
		$html = '';

		$html .= wfTemplate( 'wikibase-entityview',
			$entity->getType(),
			$entityId,
			$this->language->getCode(),
			$this->language->getDir(),
			$this->getInnerHtml( $entityRevision, $editable )
		);

		// Show loading spinner as long as JavaScript is initialising.
		// The fastest way to show it is placing the script right after the corresponding HTML.
		// Remove it after a while in any case (e.g. some resources might not have been loaded
		// silently, so JavaScript is not initialising).
		// Additionally attaching to window.error would only make sense before any other
		// JavaScript is parsed.
		$html .= Html::inlineScript( '
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
		return $html;
	}

	/**
	 * Builds and returns the inner HTML for representing a whole WikibaseEntity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 *
	 * @return string
	 */
	protected abstract function getInnerHtml( EntityRevision $entityRevision, $editable = true );

}
