/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
'use strict';

var PARENT = $.wikibase.toolbarlabel;

/**
 * Represents a toolbar button within wikibase scope.
 *
 * @extends jQuery.wikibase.toolbarlabel
 * @since 0.4
 *
 * @event action: Triggered when the button action is triggered by clicking or hitting enter on it.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.toolbarbutton', PARENT, {
	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;

		PARENT.prototype._create.call( this );

		this.element
		.addClass( this.widgetBaseClass )
		.on( 'click', function( event ) {
			if( self.isDisabled() ) { // can't do action when disabled!
				return false;
			}
			self._trigger( 'action' );
			return true;
		} );
	}

} );

} )( mediaWiki, wikibase, jQuery );
