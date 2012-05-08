/**
 * JavasScript for creating and managing a tooltip within the 'Wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Tooltip.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';

/**
 * a generic tooltip
 *
 * @param jQuery subject element the tooltip shall be attached to
 * @param String tooltip message (may contain HTML markup)
 * @param Object (optional) custom tipsy tooltip configuration
 */
window.wikibase.ui.Tooltip = function( subject, tooltipContent, tipsyConfig ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject, tooltipContent, tipsyConfig );
	}
};
window.wikibase.ui.Tooltip.prototype = {
	/**
	 * @const
	 * Class which marks the tooltip within the site html.
	 */
	UI_CLASS: 'wb-ui-toolbar-tooltip',

	/**
	 * @var jQuery element the tooltip should be attached to
	 */
	_subject: null,

	/**
	 * @var Tipsy tipsy tooltip element
	 */
	_tipsy: null,

	/**
	 * @var Object tipsy tooltip configuration vars
	 */
	_tipsyConfig: null,

	/**
	 * @var bool used to determine if tooltip message is currently visible or not
	 */
	_isVisible: false,

	/**
	 * @var bool used to determine if tooltip should react on hovering or not
	 */
	_permanent: false,

	/**
	 * initializes ui element, called by the constructor
	 *
	 * @param jQuery subject
	 * @param string tooltipContent (may contain HTML markup)
	 * @param object tipsyConfig (optional) custom tipsy tooltip configuration
	 */
	_init: function( subject, tooltipContent, tipsyConfig ) {
		this._subject = subject;
		this._subject.attr( 'title', tooltipContent );
		if ( this._tipsyConfig == null || typeof this._tipsyConfig.gravity == undefined ) {
			this._tipsyConfig = {};
			this.setGravity( 'ne' );
		}
		this._initTooltip();
	},

	/**
	 * Initializes the tooltip for the given element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery parent element
	 */
	_initTooltip: function() {
		this._subject.tipsy( {
			'gravity': this._tipsyConfig.gravity,
			'trigger': 'manual',
			'html': true
		} );

		this._tipsy = this._subject.data( 'tipsy' );

		// reposition tooltip when resizing the browser window
		$( window ).on( 'resize', $.proxy( function() {
			if ( this._isVisible ) {
				this.hideMessage(); // FIXME: better repositioning mechanism (this one is also used in EditableValue)
				this.showMessage();
			}
		}, this ) );

		this._toggleEvents( true );
	},

	/**
	 * toogle tooltip events to achive a permanent state or hover functionality
	 *
	 * @param bool activate
	 */
	_toggleEvents: function( activate ) {
		if ( activate ) {
			this._subject.on( 'mouseover', jQuery.proxy( function() { this.showMessage(); }, this ) );
			this._subject.on( 'mouseout', jQuery.proxy( function() { this.hideMessage(); }, this ) );
		} else {
			this._subject.off( 'mouseover' );
			this._subject.off( 'mouseout' );
		}
	},

	/**
	 * show tooltip
	 *
	 * @param boolean permanent whether tooltip should be displayed permanently until hide() is being
	 *        called explicitly. false by default.
	 */
	showMessage: function( permanent ) {
		if ( !this._isVisible ) {
			this._tipsy.show();
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
	hideMessage: function() {
		if ( this._permanent && typeof this._subject.data( 'events' ) == 'undefined' || !this._permanent ) {
			this._permanent = false;
			this._toggleEvents( true );
			if ( this._isVisible ) {
				this._tipsy.hide();
				this._isVisible = false;
			}
		}
	},

	/**
	 * set where the tooltip message shall appear
	 *
	 * @param String gravity
	 */
	setGravity: function( gravity ) {
		// flip horizontal direction in rtl language
		if ( document.documentElement.dir == 'rtl' ) {
			if ( gravity.search( /e/ ) != -1) {
				gravity = gravity.replace( /e/g, 'w' );
			} else {
				gravity = gravity.replace( /w/g, 'e' );
			}
		}
		this._tipsyConfig.gravity = gravity;
		if ( this._tipsy != null ) {
			this._tipsy.options.gravity = gravity;
		}
	},

	/**
	 * set tooltip message
	 *
	 * @param string message
	 */
	setMessage: function( message ) {
		this._tipsy.$element.attr( 'original-title', message );
	},


	/**
	 * destroy object
	 */
	destroy: function() {
		if ( this._isVisible ) {
			this.hideMessage();
		}
		this._tipsyConfig = null;
		this._tipsy = null;
	}

};