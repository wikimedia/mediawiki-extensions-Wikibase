/**
 * JavasSript for creating and managing the tooltip of the 'Wikibase' property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Tooltip.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

/**
 * Represents a tooltip within a wikibase.ui.PropertyEditTool.Toolbar toolbar
 *
 * @param String tooltip message
 * @param Object tipsy tooltip configuration vars
 */
window.wikibase.ui.PropertyEditTool.Toolbar.Tooltip = function( tooltipMessage, tipsyConfig ) {
	this._tipsyConfig = tipsyConfig;
	window.wikibase.ui.PropertyEditTool.Toolbar.Label.call( this, tooltipMessage );
};
window.wikibase.ui.PropertyEditTool.Toolbar.Tooltip.prototype = new window.wikibase.ui.PropertyEditTool.Toolbar.Label();
$.extend( window.wikibase.ui.PropertyEditTool.Toolbar.Tooltip.prototype, {
	/**
	 * @const
	 * Class which marks the tooltip within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar-tooltip',

	/**
	 * @var Object tipsy tooltip configuration vars
	 */
	_tipsyConfig: null,

	/**
	 * @var boolean used to determine if tooltip message is currently visible or not
	 */
	_isVisible: null,

	/**
	 * Initializes the tooltip for the given element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery parent element
	 */
	_initElem: function( tooltipMessage ) {

		// default tipsy configuration
		if ( this._tipsyConfig == null || typeof this._tipsyConfig.gravity == undefined) {
			this._tipsyConfig = {
				gravity: 'ne'
			};
		}

		var elem = $( '<span/>', {
			'class': 'mw-help-field-hint',
			title: tooltipMessage,
			style: 'display:inline'
		} ).tipsy( {
			'gravity': this._tipsyConfig.gravity,
			'trigger': 'manual'
		} );
		elem.on( 'mouseover', jQuery.proxy( function() { this.show(); }, this ) );
		elem.on( 'mouseout', jQuery.proxy( function() { this.hide(); }, this ) );

		window.wikibase.ui.PropertyEditTool.Toolbar.Label.prototype._initElem.call( this, elem );
	},

	/**
	 * show tooltip
	 *
	 * @param boolean toggle between hover and manual (fixed)
	 */
	show: function() {
		if ( !this._isVisible ) {
			$(this._elem.children()[0]).tipsy( 'show' );
			this._isVisible = true;
		}
	},

	/**
	 * hide tooltip
	 *
	 * @param boolean toggle between hover and manual (fixed)
	 */
	hide: function() {
		if ( this._isVisible ) {
			$(this._elem.children()[0]).tipsy( 'hide' );
			this._isVisible = false;
		}
	},

	destroy: function() {
		if ( this._elem ) {
			this._elem.tipsy( 'hide' );
		}
		window.wikibase.ui.PropertyEditTool.Toolbar.Label.prototype.destroy.call( this );
	}

} );