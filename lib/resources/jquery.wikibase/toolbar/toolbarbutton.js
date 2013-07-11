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
		PARENT.prototype._create.call( this );

		this.element.addClass( this.widgetBaseClass );

		this._attachEventHandlers();
	},

	/**
	 * Attaches event handlers regarding the toolbar button.
	 * @since 0.4
	 */
	_attachEventHandlers: function() {
		var self = this;

		this.element
		.off( '.' + this.widgetName )
		.on( 'click.' + this.widgetName, function( event ) {
			if( self.isDisabled() ) {
				return false;
			}
			self._trigger( 'action' );
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
	},

	/**
	 * Clones the toolbar button circumventing jQuery widget creation process.
	 * @since 0.4
	 *
	 * @param {Object} [options] Widget options to inject into cloned widget.
	 * @return {jQuery} Cloned node featuring a cloned toolbar button widget.
	 */
	clone: function( options ) {
		options = options || {};

		// Do not clone event bindings by using clone( true ) since these would trigger events on
		// the original element.
		var $clone = this.element.clone();

		// Since we cannot use clone( true ), copy the data attributes manually:
		$.each( this.element.data(), function( k, v ) {
			$clone.data( k, $.extend( true, {}, v ) );
		} );

		var clone = $clone.data( 'toolbarbutton' );

		// Update clone's element:
		clone.element = $clone;

		$.extend( clone.options, options );

		// Re-attach event handlers:
		clone._attachEventHandlers();

		// Re-assign "wikibase-toolbaritem" data attribute with updated clone:
		$clone.data( 'wikibase-toolbaritem', clone );

		return $clone;
	}

} );

} )( mediaWiki, wikibase, jQuery );
