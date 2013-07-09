/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

var PARENT = $.Widget;

/**
 * Represents a label within wikibase scope.
 *
 * @constructor
 * @extends wb.ui.Tooltip.Extension
 * @extends wb.utilities.ui.StatableObject
 * @since 0.4
 *
 * @option {boolean} isStateChangeable Whether object's state is changeable (enabled, disabled).
 */
$.widget( 'wikibase.toolbarlabel', PARENT, {
	/**
	 * Options.
	 * @type {Object}
	 */
	options: {
		stateChangeable: true
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		PARENT.prototype._create.call( this );

		if ( typeof this.options.content === 'string' ) {
			this.options.content = $.trim( this.options.content );
		}

		this.element
		.addClass( this.widgetBaseClass )
		.data( 'wikibase-toolbaritem', this );

		if( this.options.content ) {
			this.element.empty().append( this.options.content );
		}
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		if ( this._tooltip !== null ) {
			this.removeTooltip();
		}

		this.element
		.removeClass( this.baseClass )
		.removeData( 'wikibase-toolbaritem' );

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Determines whether state change (enabling, disabling) is possible for this object.
	 *
	 * @return {boolean} Whether changing the state is possible.
	 */
	isStateChangeable: function() {
		return this.options.stateChangeable;
	}

} );

// Add tooltip functionality:
wb.ui.Tooltip.Extension.useWith( $.wikibase.toolbarlabel, {
	// Overwrite required functions:
	getTooltipParent: function() {
		return this.element;
	}
} );

// Add disable/enable functionality overwriting required functions:
wb.utilities.ui.StatableObject.useWith( $.wikibase.toolbarlabel, {
	/**
	 * @see wikibase.utilities.ui.StatableObject.getState
	 */
	getState: function() {
		return ( this.element.hasClass( this.widgetBaseClass + '-disabled' ) ) ?
			this.STATE.DISABLED :
			this.STATE.ENABLED;
	},

	/**
	 * @see wikibase.utilities.ui.StatableObject.setState
	 */
	setState: function( state ) {
		if ( !this.options.stateChangeable ) { // state is not supposed to change, no need to do anything
			return true;
		}
		return wb.utilities.ui.StatableObject.prototype.setState.call( this, state );
	},

	/**
	 * @see wikibase.utilities.ui.StatableObject._setState
	 */
	_setState: function( state ) {
		if( state === this.STATE.DISABLED ) {
			this.element.addClass( this.widgetBaseClass + '-disabled' );
			// TODO: Put setting tabindex into $.wikibase.toolbarbutton
			this.element.attr( 'tabindex', '-1' );
		} else {
			this.element.removeClass( this.widgetBaseClass + '-disabled' );
			this.element.removeAttr( 'tabindex' );
		}
		return true;
	}

} );

}( wikibase, jQuery ) );
