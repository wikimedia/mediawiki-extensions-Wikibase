/**
 * Toggler widget
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < danweetz@web.de >
 *
 * The toggler hides a references subject node an toggles its visibility whenever clicking the
 * element the toggler is initialized on. The toggler considers the subject's current "display"
 * style, so if it is set to "none", it is considered invisible initially.
 *
 * @option {string} $subject (REQUIRED) The node whose visibility shall be toggled.
 *
 * @dependency jquery.ui.Widget
 */
( function( $ ) {
	'use strict';

	/**
	 * Whether page is rendered in rtl context. This, however, depends on the css class "rtl"
	 * being assigned to the body element.
	 * @type {null}
	 */
	var IS_RTL = null;

	/**
	 * CSS class for toggle elements icons.
	 * @type {String} 'ui-icon-triangle-1-e' or 'ui-icon-triangle-1-w'
	 */
	var CLS_TOGGLE_HIDDEN = 'ui-icon-triangle-1-e',
		CLS_TOGGLE_VISIBLE = 'ui-icon-triangle-1-s';

	/**
	 * Whether the user client supports CSS3 transformation.
	 * @type boolean
	 */
	var browserSupportsTransform;

	$( document ).ready( function() {
		IS_RTL = $( 'body' ).hasClass( 'rtl' );

		if( IS_RTL ) {
			CLS_TOGGLE_HIDDEN = 'ui-icon-triangle-1-w';
		}

		// Check for support of transformation (see https://gist.github.com/1031421)
		var img = (new Image).style;
		browserSupportsTransform = 'transition' in img // general
			|| 'msTransform' in img
			|| 'webkitTransition' in img; // Webkit
	} );


	$.widget( 'ui.toggler', {

		/**
		 * Additional options.
		 * @type {Object}
		 */
		options: {
			$subject: null
		},

		/**
		 * The node subject to getting toggled.
		 * @type {jQuery}
		 */
		$subject: null,

		/**
		 * The toggler's icon.
		 * @type {jQuery}
		 */
		$toggleIcon: null,

		/**
		 * @see jQuery.ui.Widget._create
		 */
		_create: function() {
			var self = this;

			if( !this.options.$subject ) {
				throw new Error( 'No subject given: Nothing to toggle.' );
			}

			var $toggleLabel = $( '<span/>' )
			.text( this.element.text() )
			.addClass( this.widgetBaseClass + '-label' );

			this.element
			.text( '' )
			.addClass( this.widgetBaseClass +  ' ' + this.widgetBaseClass + '-toggle');

			if( this.element[0].nodeName === 'A' ) {
				this.element.attr( 'href', 'javascript:void(0);' );
			}

			this.$toggleIcon = $( '<span/>' )
			.addClass( this.widgetBaseClass + '-icon ui-icon' );

			this.element
			.on( 'click', function( event ) {
				// Change toggle icon to reflect current state of toggle subject visibility:
				self._reflectVisibilityOnToggleIcon( true );
				self.options.$subject.slideToggle();
			} )
			.append( this.$toggleIcon )
			.append( $toggleLabel );

			// Consider content being invisible initially:
			this._reflectVisibilityOnToggleIcon();
		},

		/**
		 * @see jQuery.ui.Widget
		 */
		destroy: function() {
			var label = this.element.children( this.widgetBaseClass + '-label' ).text();
			this.element.empty().text( label );
			$.ui.Widget.prototype.destroy.call( this );
		},

		/**
		 * Reflects the toggler's subject visibility in the toggler's icon.
		 *
		 * @param {boolean} [inverted]
		 */
		_reflectVisibilityOnToggleIcon: function( inverted ) {
			// Don't use is( ':visible' ) which would be misleading if element not yet in DOM!
			var makeVisible = this.options.$subject.css( 'display' ) !== 'none';
			if( inverted ) {
				makeVisible = !makeVisible;
			}
			// Add classes displaying rotated icon. If CSS3 transform is available, use it:
			this.$toggleIcon.removeClass( CLS_TOGGLE_HIDDEN + ' ' + this.widgetBaseClass + '-icon3dtrans ' + CLS_TOGGLE_VISIBLE );
			if( !browserSupportsTransform ) {
				this.$toggleIcon.addClass( makeVisible ? CLS_TOGGLE_VISIBLE : CLS_TOGGLE_HIDDEN );
			} else {
				this.$toggleIcon.addClass( this.widgetBaseClass + '-icon3dtrans ' + CLS_TOGGLE_VISIBLE );
			}
			this.element[ makeVisible ? 'removeClass' : 'addClass' ]( this.widgetBaseClass + '-toggle-collapsed' );
		},

		/**
		 * Disables the toggler.
		 */
		disable: function() {
			this.element.addClass( 'ui-state-disabled' );
		},

		/**
		 * Enables the toggler.
		 */
		enable: function() {
			this.element.removeClass( 'ui-state-disabled' );
		}

	} );

} )( jQuery );
