( function () {
	'use strict';

/**
 * @property {string} iconClass='ui-icon-triangle-1-'
 * @ignore
 */
var iconClass = 'ui-icon-triangle-1-';

/**
 * Whether page is rendered in rtl context. This, however, depends on the css class "rtl"
 * being assigned to the body element.
 *
 * @property {boolean} isRtl=false
 * @ignore
 */
var isRtl = false;

/**
 * Whether the user client supports CSS3 transformation.
 *
 * @property {boolean} browserSupportsTransform=false
 * @ignore
 */
var browserSupportsTransform = false;

$( document ).ready( function() {
	// have to wait for document to be loaded for this, otherwise 'rtl' might not yet be there!
	isRtl = $( 'body' ).hasClass( 'rtl' );

	// Check for support of transformation (see https://gist.github.com/1031421)
	var style = new Image().style;
	browserSupportsTransform = 'transform' in style // general
		|| 'msTransform' in style
		|| 'webkitTransform' in style; // Webkit
} );

/**
 * Toggler widget.
 * The toggler hides a references subject node an toggles its visibility whenever clicking the
 * element the toggler is initialized on. The toggler considers the subject's current "display"
 * style, so if it is set to "none", it is considered invisible initially.
 *
 * @class jQuery.ui.toggler
 * @extends jQuery.Widget
 * @uses jQuery.animateWithEvent
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {jQuery} options.$subject
 *        The node whose visibility shall be toggled.
 * @param {boolean} [options.visible]
 *        The initial state.
 */
/**
 * @event animation
 * Triggered at the beginning of toggler animations.
 * @param {jQuery.AnimationEvent} event
 * @param {Object} params
 * @param {boolean} params.visible Whether the subject is toggled to be visible.
 */
$.widget( 'ui.toggler', {

	/**
	 * @see jQuery.Widget.options
	 * @protected
	 */
	options: {
		$subject: null,
		visible: null
	},

	/**
	 * The node subject to getting toggled.
	 *
	 * @property {jQuery}
	 * @protected
	 * @readonly
	 */
	$subject: null,

	/**
	 * The toggler's icon.
	 *
	 * @property {jQuery}
	 * @protected
	 * @readonly
	 */
	$toggleIcon: null,

	/**
	 * @see jQuery.Widget._create
	 * @protected
	 *
	 * @throws {Error} if $subject option is not specified.
	 */
	_create: function() {
		var self = this;

		if ( !this.options.$subject ) {
			throw new Error( 'No subject given: Nothing to toggle.' );
		}

		var $toggleLabel = $( '<span/>' )
		.text( this.element.text() )
		.addClass( this.widgetBaseClass + '-label' );

		var visible = this.options.visible === null
			? this._isSubjectVisible()
			: this.options.visible;

		this.element
		.text( '' )
		.addClass( this.widgetBaseClass + ' ' + this.widgetBaseClass + '-toggle ui-state-default'
			+ ( visible ? '' : ' ' + this.widgetBaseClass + '-toggle-collapsed' ) );

		this.$toggleIcon = $( '<span/>' )
		.addClass( this.widgetBaseClass + '-icon ui-icon ' + iconClass
			+ ( visible ? 's' : ( isRtl ? 'w' : 'e' ) ) );

		this.element
		.on( 'click.' + this.widgetName, function( event ) {
			event.preventDefault();
			self.toggle();
		} )
		.on( 'mouseover.' + this.widgetName, function( event ) {
			self.element.addClass( 'ui-state-hover' );
		} )
		.on( 'mouseout.' + this.widgetName, function( event ) {
			self.element.removeClass( 'ui-state-hover' );
		} )
		.append( this.$toggleIcon )
		.append( $toggleLabel );
	},

	/**
	 * Toggle status of toggler to expand/collapse.
	 */
	toggle: function () {
		var self = this;
		if ( !this.element.hasClass( 'ui-state-disabled' ) ) {
			// Change toggle icon to reflect current state of toggle subject visibility:
			var visible = this._reflectVisibilityOnToggleIcon( true );

			this.options.$subject.stop().animateWithEvent(
					'togglerstatetransition',
					'slideToggle',
					this.options,
					function( animationEvent ) {
						self._trigger( 'animation', animationEvent, {
							visible: visible
						} );
					}
			);
		}
	},

	/**
	 * Returns status of toggler.
	 * Is true when toggler is collapsed.
	 *
	 * @return {boolean}
	 */
	isCollapsed: function() {
		return this.element.hasClass( this.widgetBaseClass + '-toggle-collapsed' );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		var label = this.element.children( this.widgetBaseClass + '-label' ).text();
		this.element.empty().text( label );
		this.options.$subject.stop();
		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * @private
	 *
	 * @return {boolean}
	 */
	_isSubjectVisible: function() {
		// Don't use is( ':visible' ) which would be misleading if element not yet in DOM!
		return this.options.$subject.css( 'display' ) !== 'none';
	},

	/**
	 * Reflects the toggler's subject visibility in the toggler's icon.
	 *
	 * @private
	 *
	 * @param {boolean} [inverted]
	 * @return {boolean} Whether the subject is toggled to be visible.
	 */
	_reflectVisibilityOnToggleIcon: function( inverted ) {
		var visible = this._isSubjectVisible();
		if ( inverted ) {
			visible = !visible;
		}

		this.$toggleIcon.removeClass( iconClass + 'e ' + iconClass + 's ' + iconClass + 'w '
			+ this.widgetBaseClass + '-icon3dtrans' );
		// Add classes displaying rotated icon. If CSS3 transform is available, use it:
		if ( !browserSupportsTransform || !$.speed().duration ) {
			this.$toggleIcon.addClass( iconClass + ( visible ? 's' : ( isRtl ? 'w' : 'e' ) ) );
		} else {
			this.$toggleIcon.addClass( iconClass + 's '
				+ this.widgetBaseClass + '-icon3dtrans' );
		}

		this.element[ visible ? 'removeClass' : 'addClass' ](
			this.widgetBaseClass + '-toggle-collapsed' );

		return visible;
	},

	/**
	 * Refreshes the toggler's state.
	 */
	refresh: function() {
		this._reflectVisibilityOnToggleIcon();
	}
} );

}() );
