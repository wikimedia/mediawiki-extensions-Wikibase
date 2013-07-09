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

		// Disable button and attach tooltip when editing is restricted. Registering the event
		// handler once is enough.
		// TODO: Disabling "edit" actions/buttons should be done from out of the PropertyEditTool.
		if( $( '.' + this.widgetBaseClass ).length === 0 ) {
			// Can only find buttons that are in the DOM. However, the event handler is not needed
			// more than one. At least, remove previously attached handler to not have it registered
			// twice.
			$( wb )
			.off( '.' + this.widgetName )
			.on( 'restrictEntityPageActions.' + this.widgetName
				+ ' blockEntityPageActions.' + this.widgetName, function( event ) {

				$( '.' + self.widgetBaseClass ).each( function( i, node ) {
					var toolbarButton = $( node ).data( self.widgetName );

					toolbarButton.disable();

					var messageId = ( event.type === 'blockEntityPageActions' )
						? 'wikibase-blockeduser-tooltip-message'
						: 'wikibase-restrictionedit-tooltip-message';

					toolbarButton.setTooltip( mw.message( messageId ).escaped() );

					toolbarButton._tooltip.setGravity( 'nw' );
				} );
			} );
		}
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		PARENT.prototype.destroy.call( this );

		if( $( '.' + this.widgetBaseClass ).length === 0 ) {
			$( wb ).off( '.' + this.widgetName );
		}
	}

} );

} )( mediaWiki, wikibase, jQuery );
