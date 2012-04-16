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
	 * Initializes the tooltip for the given element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery parent element
	 */
	_initElem: function( tooltipMessage ) {
		if ( this._tipsyConfig == null ) {
			this._tipsyConfig = {
				gravity: 'ne',
				trigger: 'hover'
			};
		}
		if ( typeof this._tipsyConfig.gravity == undefined ) {
			this._tipsyConfig.gravity = 'ne';
		}
		if ( typeof this._tipsyConfig.trigger == undefined ) {
			this._tipsyConfig.trigger = 'hover';
		}

		var elem = $( '<span/>', {
			'class': 'mw-help-field-hint',
			title: tooltipMessage,
			style: 'display:inline'
		} ).tipsy( {
			'gravity': this._tipsyConfig.gravity,
			'trigger': this._tipsyConfig.trigger
		} );

		window.wikibase.ui.PropertyEditTool.Toolbar.Label.prototype._initElem.call( this, elem );
	},

	/**
	 * toogle tooltip to react on hover events or not
	 */
	/*
	_toggleEvents: function() {
		console.log('_toggleEvents: ');
		console.log(this._tooltip.data('events'));
		if ( typeof this._tooltip.data( 'events' ) == 'undefined' ) {
			this._tooltip.on( 'mouseover', jQuery.proxy( function() { this.show(); }, this ) );
			this._tooltip.on( 'mouseout', jQuery.proxy( function() { this.hide(); }, this ) );
		} else {
			this._tooltip.off( 'mouseover' );
			this._tooltip.off( 'mouseout' );
		}
		console.log(typeof this._tooltip.data('events'));
	},
	*/

	/**
	 * show tooltip
	 *
	 * @param boolean toggle between hover and manual (fixed)
	 */
	showTooltip: function( toggleEvents ) {
		this._elem.tipsy('show');
		//if ( toggleEvents ) this._toggleEvents();
	},

	/**
	 * hide tooltip
	 *
	 * @param boolean toggle between hover and manual (fixed)
	 */
	hideTooltip: function( toggleEvents ) {
		this._elem.tipsy('hide');
		//if ( toggleEvents ) this._toggleEvents();
	},
	
	destroy: function() {
		if ( this._elem ) {
			this._elem.tipsy( 'hide' );
		}
		window.wikibase.ui.PropertyEditTool.Toolbar.Label.prototype.destroy.call( this );
	}

} );