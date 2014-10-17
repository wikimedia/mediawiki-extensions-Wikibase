/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
	'use strict';

var $window = $( window ),
	stickyInstances = [],
	PLUGIN_NAME = 'sticknode';

/**
 * jQuery sticknode plugin.
 * Sticks a node with "position: fixed" when vertically scrolling it out of the viewport.
 *
 * @param {Object} [options]
 *        - {jQuery} $container
 *          Node specifying the bottom boundary for the node the plugin is initialized on. If the
 *          node the plugin is initialized on clips out of the container, it is reset to static
 *          position.
 * @return {jQuery}
 *
 * @event sticknodeupdate
 *        Triggered when the node the widget is initialized on updates its positioning behaviour.
 *        - {jQuery.Event}
 */
$.fn.sticknode = function( options ) {
	options = options || {};

	this.each( function() {
		var $node = $( this );

		if( $node.data( PLUGIN_NAME ) ) {
			return;
		}

		register( new StickyNode( $( this ), options ) );
	} );

	return this;
};

/**
 * @param {boolean} [force]
 */
function update( force ) {
	force = force === true;

	for( var i = 0; i < stickyInstances.length; i++ ) {
		if( stickyInstances[i].update( $window.scrollTop(), force ) ) {
			stickyInstances[i].$node.triggerHandler( PLUGIN_NAME + 'update' );
		}
	}
}

function updateAndForceTriggerEvent() {
	update( true );
}

/**
 * @param {Function} fn
 * @return {Function}
 */
function throttle( fn ) {
	return $.throttle ? $.throttle( 150, fn ) : fn;
}

/**
 * @param {StickyNode} sticky
 */
function register( sticky ) {
	if( !stickyInstances.length ) {
		$window
		.on( 'scroll.' + PLUGIN_NAME + ' ' + 'touchmove.' + PLUGIN_NAME, ( function() {
			return throttle( update );
		}() ) )
		.on( 'resize.' + PLUGIN_NAME, ( function() {
			return throttle( updateAndForceTriggerEvent );
		}() ) );
	}
	stickyInstances.push( sticky );
}

/**
 * @param {StickyNode} sticky
 */
function deregister( sticky ) {
	var index = $.inArray( sticky );
	if( index ) {
		stickyInstances.splice( index, 1 );
	}
	if( !stickyInstances.length ) {
		$window.off( '.' + PLUGIN_NAME );
	}
}

/**
 * @constructor
 *
 * @param {jQuery} $node
 * @param {Object} options
 */
var StickyNode = function( $node, options ) {
	this.$node = $node;
	this.$node.data( PLUGIN_NAME, this );

	this._options = $.extend( {
		$container: null
	}, options );

	this._initialAttributes = {};
};

$.extend( StickyNode.prototype, {
	/**
	 * @type {jQuery}
	 */
	$node: null,

	/**
	 * @type {Object}
	 */
	_options: null,

	/**
	 * @type {Object}
	 */
	_initialAttributes: null,

	/**
	 * @type {boolean}
	 */
	_changesDocumentHeight: false,

	/**
	 * Destroys and deregisters the plugin.
	 */
	destroy: function() {
		deregister( this );
		this.$node.removeData( PLUGIN_NAME );
	},

	/**
	 * @return {boolean}
	 */
	_clipsContainer: function() {
		if( !this._options.$container || !this.isFixed() ) {
			return false;
		}

		var nodeBottom = this.$node.offset().top + this.$node.outerHeight();

		var containerBottom = this._options.$container.offset().top
			+ this._options.$container.outerHeight();

		return nodeBottom > containerBottom;
	},

	/**
	 * @return {boolean}
	 */
	_isScrolledAfterContainer: function() {
		if( !this._options.$container ) {
			return false;
		}

		var containerBottom = this._options.$container.offset().top
			+ this._options.$container.outerHeight();

		return $window.scrollTop() + this.$node.outerHeight() > containerBottom;
	},

	/**
	 * @param {number} scrollTop
	 * @return {boolean}
	 */
	_isScrolledBeforeContainer: function( scrollTop ) {
		if( !this._initialAttributes.offset ) {
			return false;
		}

		var initTopOffset = this._initialAttributes.offset.top;

		return !this._changesDocumentHeight && scrollTop < initTopOffset
			|| this._changesDocumentHeight && scrollTop < initTopOffset - this.$node.outerHeight();
	},

	_fix: function() {
		if( this.isFixed() ) {
			return;
		}

		this._initialAttributes = {
			offset: this.$node.offset(),
			position: this.$node.css( 'position' ),
			top: this.$node.css( 'top' ),
			left: this.$node.css( 'left' )
		};

		this.$node
		.css( 'left', this._initialAttributes.offset.left + 'px' )
		.css( 'top', -1 * ( this.$node.outerHeight( true ) - this.$node.outerHeight() ) )
		.css( 'position', 'fixed' );
	},

	_unfix: function() {
		this.$node
		.css( 'left', this._initialAttributes.left )
		.css( 'top', this._initialAttributes.top )
		.css( 'position', this._initialAttributes.position );

		this._initialAttributes.offset = null;
	},

	/**
	 * Returns whether the node the plugin is initialized on is in "fixed" position.
	 *
	 * @return {boolean}
	 */
	isFixed: function() {
		return this.$node.css( 'position' ) === 'fixed';
	},

	/**
	 * Updates the node's positioning behaviour according to a specific scroll offset.
	 *
	 * @param {number} scrollTop
	 * @param {boolean} force
	 * @return {boolean}
	 */
	update: function( scrollTop, force ) {
		var changedState = false,
			$document = $( document ),
			initialDocumentHeight = $document.height(),
			newDocumentHeight;

		if( force && this.isFixed() ) {
			this._unfix();
		}

		if(
			!this.isFixed()
			&& scrollTop > this.$node.offset().top
			&& !this._isScrolledAfterContainer()
		) {
			this._fix();

			newDocumentHeight = $document.height();
			if( newDocumentHeight < initialDocumentHeight ) {
				$window.scrollTop( scrollTop - ( initialDocumentHeight - newDocumentHeight ) );
				initialDocumentHeight = newDocumentHeight;
				this._changesDocumentHeight = true;
			}

			changedState = true;
		}

		if(
			this.isFixed() && this._isScrolledBeforeContainer( scrollTop )
			|| this._clipsContainer()
		) {
			this._unfix();

			newDocumentHeight = $document.height();
			if( newDocumentHeight > initialDocumentHeight ) {
				$window.scrollTop( scrollTop + ( newDocumentHeight - initialDocumentHeight ) );
				this._changesDocumentHeight = true;
			}

			changedState = !changedState;
		}

		return changedState;
	}
} );

}( jQuery ) );