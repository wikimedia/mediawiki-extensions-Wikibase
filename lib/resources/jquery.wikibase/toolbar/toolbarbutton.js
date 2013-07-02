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
 * Represents a button within wikibase scope.
 *
 * @constructor
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

		// disable button and attach tooltip when editing is restricted
		$( wb ).on( 'restrictEntityPageActions blockEntityPageActions', function( event ) {
			self.disable();

			var messageId = ( event.type === 'blockEntityPageActions' )
				? 'wikibase-blockeduser-tooltip-message'
				: 'wikibase-restrictionedit-tooltip-message';

			self.setTooltip( mw.message( messageId ).escaped() );

			self._tooltip.setGravity( 'nw' );
		} );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.wikibase.toolbarlabel.setFocus
	 */
	setFocus: function() {
		this.element.focus();
	}

} );

} )( mediaWiki, wikibase, jQuery );
