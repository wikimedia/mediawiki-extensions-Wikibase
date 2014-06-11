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
 * @option {jQuery} $subject (REQUIRED) The node whose visibility shall be toggled.
 *
 * @event animation Triggered at the beginning of toggler animations.
 *        (1) {jQuery.AnimationEvent} event
 *
 * @dependency jQuery
 * @dependency jQuery.Widget
 * @dependency jQuery.animateWithEvent
 */
( function( $ ) {
	'use strict';

	/**
	 * Whether page is rendered in rtl context. This, however, depends on the css class "rtl"
	 * being assigned to the body element.
	 * @type {boolean|null}
	 */
	var isRtl;

	/**
	 * Whether the user client supports CSS3 transformation.
	 * @type {boolean|null}
	 */
	var browserSupportsTransform;

	$( document ).ready( function() {
		// have to wait for document to be loaded for this, otherwise 'rtl' might not yet be there!
		isRtl = $( 'body' ).hasClass( 'rtl' );

		// Check for support of transformation (see https://gist.github.com/1031421)
		var style = new Image().style;
		browserSupportsTransform = 'transform' in style // general
			|| 'msTransform' in style
			|| 'webkitTransform' in style; // Webkit
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
		 * @see jQuery.Widget._create
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
			.addClass( this.widgetBaseClass + ' ' + this.widgetBaseClass + '-toggle '
				+ 'ui-state-default' );

			if( this.element[0].nodeName === 'A' ) {
				this.element.attr( 'href', 'javascript:void(0);' );
			}

			this.$toggleIcon = $( '<span/>' )
			.addClass( this.widgetBaseClass + '-icon ui-icon' );

			this.element
			.on( 'click.' + this.widgetName, function( event ) {
				if( !self.element.hasClass( 'ui-state-disabled' ) ) {
					// Change toggle icon to reflect current state of toggle subject visibility:
					self._reflectVisibilityOnToggleIcon( true );

					self.options.$subject.stop().animateWithEvent(
						'togglerstatetransition',
						'slideToggle',
						self.options,
						function( animationEvent ) {
							self._trigger( 'animation', animationEvent );
						}
					);
				}
			} )
			.on( 'mouseover.' + this.widgetName, function( event ) {
				self.element.addClass( 'ui-state-hover' );
			} )
			.on( 'mouseout.' + this.widgetName, function( event ) {
				self.element.removeClass( 'ui-state-hover' );
			} )
			.append( this.$toggleIcon )
			.append( $toggleLabel );

			// Consider content being invisible initially:
			this._reflectVisibilityOnToggleIcon();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			var label = this.element.children( this.widgetBaseClass + '-label' ).text();
			this.element.empty().text( label );
			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * Reflects the toggler's subject visibility in the toggler's icon.
		 *
		 * @param {boolean} [inverted]
		 */
		_reflectVisibilityOnToggleIcon: function( inverted ) {
			var iconClass = 'ui-icon-triangle-1-',
				dir = ( isRtl === undefined ? $( 'body' ).hasClass( 'rtl' ) : isRtl ) ? 'w' : 'e',
			// Don't use is( ':visible' ) which would be misleading if element not yet in DOM!
				visible = this.options.$subject.css( 'display' ) !== 'none';
			if( inverted ) {
				visible = !visible;
			}

			this.$toggleIcon.removeClass( iconClass + 'e ' + iconClass + 's ' + iconClass + 'w '
				+ this.widgetBaseClass + '-icon3dtrans' );
			// Add classes displaying rotated icon. If CSS3 transform is available, use it:
			if( !browserSupportsTransform || !$.speed().duration ) {
				this.$toggleIcon.addClass( iconClass + ( visible ? 's' : dir ) );
			} else {
				this.$toggleIcon.addClass( iconClass + 's '
					+ this.widgetBaseClass + '-icon3dtrans' );
			}

			this.element[ visible ? 'removeClass' : 'addClass' ](
				this.widgetBaseClass + '-toggle-collapsed' );
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
