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
 * @param Object (optional, default: { gravity: 'ne' }) custom tipsy tooltip configuration
 * @param bool (optional, default: false) whether the tooltip is an error tooltip being displayed in red colors
 */
window.wikibase.ui.Tooltip = function( subject, tooltipContent, tipsyConfig, isError ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject, tooltipContent, tipsyConfig, isError );
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
	 * @var bool basically defines if the tooltip will appear in standard or error color schema
	 */
	_isError: false,

	/**
	 * @var jQuery storing DOM content that should be displayed as tooltip bubble content
	 */
	_DomContent: null,

	/**
	 * initializes ui element, called by the constructor
	 *
	 * @param jQuery subject
	 * @param string tooltipContent (may contain HTML markup)
	 * @param object tipsyConfig (optional) custom tipsy tooltip configuration
	 * @param bool (optional, default: false) whether the tooltip is an error tooltip being displayed in red colors
	 */
	_init: function( subject, tooltipContent, tipsyConfig, isError ) {
		this._subject = subject;
		if ( typeof tooltipContent == 'string' ) {
			this._subject.attr( 'title', tooltipContent );
		} else {
			/* init tipsy with some placeholder since the tooltip message would not show without the title attribute
			being set; however, setting a complex HTML structure cannot be done via the title tag, so the content is
			stored in a custom variable that will be injected when the message is triggered to show */
			this._subject.attr( 'title', '.' );
			this._DomContent = tooltipContent;
		}
		if ( typeof tipsyConfig != 'undefined' ) {
			this._tipsyConfig = tipsyConfig;
		}
		if ( this._tipsyConfig == null || typeof this._tipsyConfig.gravity == undefined ) {
			this._tipsyConfig = {};
			this.setGravity( 'ne' );
		}
		this._isError = ( typeof isError != 'undefined' ) ? isError : false;
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
	 * query whether hover events are attached
	 */
	_hasEvents: function() {
		if ( typeof this._subject.data( 'events' ) == 'undefined' ) {
			return false;
		} else {
			return (
				typeof this._subject.data( 'events' ).mouseover != 'undefined' &&
				typeof this._subject.data( 'events' ).mouseout != 'undefined'
			);
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
			if ( this._isError ) {
				this._tipsy.$tip.addClass( 'wb-error' );
			}
			if ( this._DomContent != null ) {
				this._tipsy.$tip.find('.tipsy-inner').empty();
				this._tipsy.$tip.find('.tipsy-inner').append( this._DomContent );
			}
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
		if ( this._permanent && !this._hasEvents() || !this._permanent ) {
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
	 * set tooltip message / HTML content
	 *
	 * @param jQuery|string content
	 */
	setContent: function( content ) {
		this._DomContent = null;
		if ( typeof content == 'string' ) {
			this._tipsy.$element.attr( 'original-title', content );
		} else {
			this._DomContent = content;
		}
	},

	/**
	 * destroy object
	 */
	destroy: function() {
		if ( this._isVisible ) {
			this.hideMessage();
		}
		this._toggleEvents( false );
		this._tipsyConfig = null;
		this._tipsy = null;
	}

};