/**
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
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

		this.element.addClass( this.widgetFullName );

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
			if( self.options.disabled ) {
				return false;
			}
			self._trigger( 'action' );
		} );
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

} )( jQuery );
