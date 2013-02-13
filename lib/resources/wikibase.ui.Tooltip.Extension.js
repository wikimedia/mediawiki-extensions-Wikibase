/**
 * JavasScript for creating and managing a tooltip within the 'Wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';

/**
 * Allows to extend random elements (like label or interface) with tooltip functionality
 * @constructor
 * @extension
 *
 * @example:
 * var SomeConstructor = wb.utilities.inherit( Object, function() {...} );
 * wb.ui.Tooltip.Ext.extend( SomeConstructor ); // makes setTooltip and other functions available
 * SomeConstructor.prototype._getTooltipParent = function() { return this.someNode };
 * SomeConstructor.setTooltip( someTooltip );
 *
 * @since 0.1
 */
wb.ui.Tooltip.Extension = wb.utilities.newExtension( {
	/**
	 * @var wikibase.ui.Tooltip tooltip attached to this label
	 */
	_tooltip: null,

	/**
	 * Returns the node the tooltip should be attached to if setTooltip() will build a tooltip instead
	 * of using a given, already built tooltip.
	 *
	 * @return jQuery
	 *
	 * @see wb.utilities.abstractFunction
	 */
	_getTooltipParent: wb.utilities.abstractFunction,

	/**
	 * Attaches a tooltip message to the extended element.
	 *
	 * @param {string|wb.RepoApiError|wb.ui.Tooltip} tooltip message to be displayed as tooltip, object
	 *        containing error information or an instantiated Tooltip object
	 * @return {wb.ui.Tooltip}
	 */
	setTooltip: function( tooltip ) {
		// if last tooltip was visible, we make the new one visible as well
		var wasVisible = false;

		if ( this._tooltip !== null ) {
			// remove existing tooltip first!
			wasVisible = this._tooltip.isVisible();
			this.removeTooltip();
		}
		if ( typeof tooltip == 'string' ) {
			// build new tooltip from string:
			this._getTooltipParent().attr( 'title', tooltip );
			this._tooltip = new wb.ui.Tooltip( this._getTooltipParent(), {}, tooltip );
		} else if ( tooltip instanceof wb.ui.Tooltip ) {
			this._tooltip = tooltip;
		} else if ( tooltip instanceof wb.RepoApiError ) {
			this._tooltip = new wb.ui.Tooltip( this._getTooltipParent(), {}, tooltip, { gravity: 'nw' } );
		}
		// restore previous tooltips visibility:
		if( this._tooltip !== null ) {
			if( wasVisible ) {
				this._tooltip.show();
			} else {
				this._tooltip.hide();
			}
		}

		if ( this._tooltip._error != null ) {
			this._tooltip.one( 'clickOutside', $.proxy( function( event ) {
				this.removeTooltip();
			}, this ) );
		}

		return this._tooltip;
	},

	/**
	 * remove a tooltip message attached to this element
	 *
	 * @return bool whether a tooltip was set
	 */
	removeTooltip: function() {
		if ( this._tooltip !== null ) {
			this._tooltip.destroy();
			this._tooltip = null;
			return true;
		}
		return false;
	},

	/**
	 * Returns the element's tooltip or null in case none is set yet
	 *
	 * @return wb.ui.Tooltip|null
	 */
	getTooltip: function() {
		return this._tooltip;
	}
} );

} )( mediaWiki, wikibase, jQuery );
