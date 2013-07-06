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
 */
$.widget( 'wikibase.wblabel', PARENT, {
	/**
	 * Default options.
	 * @type {Object}
	 */
	options: {
		content: null,
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
	 * Sets the label's content
	 *
	 * @param {string|jQuery} content
	 */
	setContent: function( content ) {
		this.element.empty();
		if ( typeof content === 'string' ) {
			content = $.trim( content );
		}
		this.element.append( content );
	},

	/**
	 * Returns the labels content. If only text was set as content, a string will be returned, if
	 * HTML nodes were set, this will return a jQuery object.
	 *
	 * @return {jQuery|string}
	 */
	getContent: function() {
		var contents = this.element.contents();

		if( contents.length === 1 && contents[0].nodeType === 3 ) {
			// return the text
			return contents.text();
		}
		// return jQuery object
		return contents;
	},

	/**
	 * Determines whether state change (enabling, disabling) is possible for this object.
	 *
	 * @return {boolean} Whether changing the state is possible.
	 */
	isStateChangeable: function() {
		return this.options.stateChangeable;
	},

	/**
	 * Sets focus on this label.
	 */
	setFocus: function() {
		this._makeFocusable();
		this.element.focus();
	},

	/**
	 * Removes focus from this label.
	 */
	removeFocus: function() {
		this.element.blur();
	},

	/**
	 * Applies tab index since regular HTML elements cannot be focused.
	 */
	_makeFocusable: function() {
		var self = this;
		this.element.attr( 'tabIndex', '0' );
		// Do not apply the tab index permanently:
		this.element.one( 'blur', function( event ) {
			self.element.removeAttr( 'tabIndex' );
		} );
	}

} );

// add tooltip functionality to EditableValue:
wb.ui.Tooltip.Extension.useWith( $.wikibase.wblabel, {
	// overwrite required functions:
	getTooltipParent: function() {
		return this.element;
	}
} );

// add disable/enable functionality overwriting required functions
wb.utilities.ui.StatableObject.useWith( $.wikibase.wblabel, {
	/**
	 * @see wb.utilities.ui.StatableObject.getState
	 */
	getState: function() {
		return ( this.element.hasClass( this.widgetBaseClass + '-disabled' ) ) ?
			this.STATE.DISABLED :
			this.STATE.ENABLED;
	},

	/**
	 * @see wb.utilities.ui.StatableObject.setState
	 */
	setState: function( state ) {
		if ( !this.options.stateChangeable ) { // state is not supposed to change, no need to do anything
			return true;
		}
		return wb.utilities.ui.StatableObject.prototype.setState.call( this, state );
	},

	/**
	 * @see wb.utilities.ui.StatableObject._setState
	 */
	_setState: function( state ) {
		if( state === this.STATE.DISABLED ) {
			this.element.addClass( this.widgetBaseClass + '-disabled' );
			// TODO: Put setting tabindex into $.wikibase.wbbutton
			this.element.attr( 'tabindex', '-1' );
		} else {
			this.element.removeClass( this.widgetBaseClass + '-disabled' );
			this.element.removeAttr( 'tabindex' );
		}
		return true;
	}

} );

}( wikibase, jQuery ) );
