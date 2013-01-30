/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Class for toggle elements icons
	 * @type {String} 'ui-icon-triangle-1-e' or 'ui-icon-triangle-1-w'
	 */
	var CLS_TOGGLE_HIDDEN = 'ui-icon-triangle-1-w',
		CLS_TOGGLE_VISIBLE = 'ui-icon-triangle-1-s';

	$( document ).ready( function() {
		// have to wait for document to be loaded for this, otherwise 'rtl' might not yet be there!
		CLS_TOGGLE_HIDDEN = 'ui-icon-triangle-1-' + ( $( 'body' ).hasClass( 'rtl' ) ? 'w' : 'e' );
	} );

	/**
	 * UI related utilities required by 'Wikibase' extension.
	 * @type {Object}
	 */
	wb.utilities.ui = {};

	/**
	 * Serves the DOM for a simple toggle. Will wrap a given node or text within a new div which
	 * will then be returned. When clicking the div, a node given in the second argument will be
	 * hidden, clicking the toggle again will make it visible again.
	 * The toggle considers the toggle subject's current 'display' style, so if it is set to 'none',
	 * it is considered invisible initially.
	 *
	 * @since 0.4
	 *
	 * @param {jQuery|string} toggleLabel The node or a message which will act as toggle
	 * @param {jQuery} $toggleSubject The element which should be toggled
	 * @return jQuery
	 */
	wb.utilities.ui.buildToggle = function( toggleLabel, $toggleSubject ) {
		var $toggle = $( '<a/>', { href: 'javascript:void(0);', 'class': 'wb-toggle' } );
		var $toggleIcon = $( '<span/>', { 'class': 'ui-icon ' + CLS_TOGGLE_HIDDEN } );
		var $toggleLabel = (  toggleLabel instanceof $ )
			? toggleLabel
			: $( '<span/>', { text: toggleLabel } );

		$toggleLabel.addClass( 'wb-toggle-label' );

		var fnReflectVisibilityOnToggleIcon = function( inverted ) {
			// don't use is( ':visible' ) which would be misleading if  element not yet in DOM!
			var makeVisible = $toggleSubject.css( 'display' ) !== 'none';
			if( inverted ) {
				makeVisible = !makeVisible;
			}
			$toggleIcon.removeClass( CLS_TOGGLE_HIDDEN + ' ' + CLS_TOGGLE_VISIBLE )
			.addClass( makeVisible ? CLS_TOGGLE_VISIBLE : CLS_TOGGLE_HIDDEN );
		};
		// consider content being invisible initially:
		fnReflectVisibilityOnToggleIcon();

		$toggle
		.on( 'click', function( event ) {
			fnReflectVisibilityOnToggleIcon( true );
			$toggleSubject.slideToggle();
			// change toggle icon to reflect current state of toggle subject visibility:

		} )
		.append( $toggleIcon )
		.append( $toggleLabel );

		return $toggle;
	};

	/**
	 * Creates a pretty link to a entity's page. Expects information about the Entity as a plain
	 * Object with 'id', 'url' and 'label' fields. If the label is not set or empty, then the link
	 * will show the entity's ID and some explanatory text describing that the label hast not been
	 * set yet.
	 *
	 * @since 0.4
	 *
	 * @param {Object} entityData Requires 'url', 'id' and optionally 'label' fields
	 * @return jQuery 'a' element
	 */
	wb.utilities.ui.buildEntityLink = function( entityData ) {
		var $propertyLink = $( '<a/>', {
			href: entityData.url,
			text: entityData.label || entityData.id
		} );

		if( !entityData.label ) {
			$propertyLink.append( $( '<span/>', {
				'class': 'wb-entity-undefinedinfo',
				'text': ' (' + mw.msg( 'wikibase-label-empty' ) + ')'
			} ) );
		}

		return $propertyLink;
	};

}( mediaWiki, wikibase, jQuery ) );
