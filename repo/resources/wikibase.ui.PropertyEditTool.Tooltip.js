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

window.wikibase.ui.PropertyEditTool.Tooltip = function( appendTo, tooltipMessage ) {
	if( typeof appendTo != 'undefined' && typeof tooltipMessage != 'undefined' ) {
		this._init( appendTo, tooltipMessage );
	}
};
window.wikibase.ui.PropertyEditTool.Tooltip.prototype = {
	/**
	 * @const
	 * Class which marks the toolbar within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittooltip',

	/**
	 * @var jQuery reference to parent element
	 */
	_parent: null,

	/**
	 * @var jQuery reference to the tooltip
	 */
	_tooltip: null,

	/**
	 * @var String message to be displayed within the tooltip
	 */
	_message: null,

	/**
	 * Initializes the tooltip for the given element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery parent element
	 */
	_init: function( parent, message ) {
		if( this._parent !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}

		this._parent = parent;
		this._message = message;

		this._buildTooltip( );
	},

	/**
	 * build tooltip and append to parent element
	 *
	 * @param: String message to be displayed within the tooltip
	 */
	_buildTooltip: function() {
		console.log('_buildTooltip: '+this._message);
		this._tooltip = $( '<span/>', {
			'class': 'mw-help-field-hint',
			'title': this._message,
			'style': 'display:inline'
		}).tipsy( {
				'gravity': 'ne',
				'trigger': 'manual'
			} );
		this._toggleEvents();
		this._parent.append( this._tooltip );
	},

	/**
	 * toogle tooltip to react on hover events or not
	 */
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

	/**
	 * show tooltip
	 *
	 * @param boolean toggle between hover and manual (fixed)
	 */
	show: function( toggleEvents ) {
		console.log('show');
		this._tooltip.tipsy('show');
		if ( toggleEvents ) this._toggleEvents();
	},

	/**
	 * hide tooltip
	 *
	 * @param boolean toggle between hover and manual (fixed)
	 */
	hide: function( toggleEvents ) {
		console.log('hide');
		this._tooltip.tipsy('hide');
		if ( toggleEvents ) this._toggleEvents();
	},

	/**
	 * destroy tooltip
	 */
	destroy: function() {
		console.log('destroy');
		this._tooltip.tipsy( 'hide' );
	}

};
