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
"use strict";

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
	 * tipsy element
	 * @var Tipsy
	 */
	_tipsy: null,

	/**
	 * @var Object tipsy tooltip configuration vars
	 */
	_tipsyConfig: null,

	/**
	 * @var boolean used to determine if tooltip message is currently visible or not
	 */
	_isVisible: false,
	
	_permanent: false,
	
	/**
	 * Initializes the tooltip for the given element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery parent element
	 */
	_initElem: function( tooltipMessage ) {
		// default tipsy configuration
		if ( this._tipsyConfig == null || typeof this._tipsyConfig.gravity == undefined ) {
			this._tipsyConfig = {
				gravity: ( document.documentElement.dir == 'rtl' ) ? 'nw' : 'ne'
			};
		}

		var tooltip = $( '<span/>', {
			'class': 'mw-help-field-hint',
			title: tooltipMessage,
			style: 'display:inline',
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css) */
		} ).tipsy( {
			'gravity': this._tipsyConfig.gravity,
			'trigger': 'manual'
		} );

		this._tipsy = tooltip.data( 'tipsy' );

		window.wikibase.ui.PropertyEditTool.Toolbar.Label.prototype._initElem.call( this, tooltip );
		
		this._toggleEvents( true );
	},
	
	_toggleEvents: function( activate ) {
		if ( activate ) {
			this._elem.on( 'mouseover', jQuery.proxy( function() { this.show(); }, this ) );
			this._elem.on( 'mouseout', jQuery.proxy( function() { this.hide(); }, this ) );
		} else {
			this._elem.off( 'mouseover' );
			this._elem.off( 'mouseout' );
		}
	},

	/**
	 * show tooltip
	 *
	 * @param boolean permanent whether tooltip should be displayed permanently until hide() is being
	 *        called explicitly. false by default.
	 */
	show: function( permanent ) {
		if ( !this._isVisible ) {
			$( this._elem.children()[0] ).tipsy( 'show' );
			this._isVisible = true;
		}
		if( permanent === true ) {
			this._toggleEvents( false );
			this._permanent = true;
		}
	},

	/**
	 * hide tooltip
	 */
	hide: function( ) {
		if ( this._permanent && typeof this._elem.data( 'events' ) == 'undefined' || !this._permanent ) {
			this._permanent = false;
			this._toggleEvents( true );
			if ( this._isVisible ) {
				$( this._elem.children()[0] ).tipsy( 'hide' );
				this._isVisible = false;
			}
		}
	},

	destroy: function() {
		if ( this._elem ) {
			if ( this._isVisible ) {
				this.hide();
			}
			this._elem.remove();
		}
	}

} );