( function () {
	'use strict';

/**
 * Caches whether the widget is used in a rtl context. This, however, depends on using an "rtl"
 * class on the document body like it is done in MediaWiki.
 *
 * @property {boolean} [IS_RTL=false]
 * @ignore
 */
var IS_RTL = false;

$( document ).ready( function() {
	IS_RTL = $( 'body' ).hasClass( 'rtl' );
} );

/**
 * Collection for keeping track which input extender widgets have their extension shown at the
 * moment.
 *
 * @property {jQuery.ui.inputextender} [inputExtendersWithVisibleExtension=Object]
 * @ignore
 */
var inputExtendersWithVisibleExtension = ( function() {
	var inputExtenders = [];
	return {
		add: function( inputExtenderInstance ) {
			var index = $.inArray( inputExtenderInstance, inputExtenders );
			if ( index < 0 ) {
				inputExtenders.push( inputExtenderInstance );
			}
		},
		remove: function( inputExtenderInstance ) {
			var index = $.inArray( inputExtenderInstance, inputExtenders );
			if ( index > -1 ) {
				inputExtenders.splice( index, 1 );
			}
		},
		get: function() {
			// Make sure this is up to date and they are really visible.
			return $.grep( inputExtenders, function( inputExtenderInstance ) {
				return inputExtenderInstance.extensionIsVisible();
			} );
		}
	};
}() );

/**
 * Input extender widget
 *
 * The input extender extends an input element with additional contents displayed underneath the.
 * (uses `jQuery.animateWithEvent`, `jQuery.ui.position`)
 *
 * @class jQuery.ui.inputextender
 * @extends jQuery.Widget
 * @uses jQuery.event.special.eachchange
 * @uses jQuery.ui
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {Object} [options]
 * @param {jQuery[]} [options.content=[]]
 *        Default/"fixed" extender contents that always should be visible as long as the extension
 *        itself is visible.
 * @param {Function|null} [options.initCallback=null]
 *        Function triggered before the extension is being shown for the first time. This may be
 *        used to init some widgets that need to be visible on initialization for measuring
 *        dimension according to their container's styles.
 *        Context of the callback is the widget, first parameter is the extension's DOM in a jQuery
 *        container.
 * @param {boolean} [options.hideWhenInputEmpty=true]
 *        Whether all of the input extender's contents shall be hidden when the associated input
 *        element is empty.
 * @param {string} [options.contentAnimationEvents='']
 *        One or more events (separated with spaces) which imply that the input extenders
 *        extension's content is about to be animated. Those events should give a
 *        `jQuery.AnimationEvent` object as their event object. If this is the case and the event
 *        bubbles up to the input extender's extension node, then this will trigger the
 *        `contentanimation` event on the widget node.
 */
/**
 * @event animation
 * Triggered at the beginning of an animation of the input's extension.
 * @param {jQuery.AnimationEvent} animationEvent
 */
/**
 * @event contentanimation
 * Triggered at the beginning of an animation of the extender's extension content. Depends on the
 * "contentAnimationEvents" option.
 * @param {jQuery.AnimationEvent} animationEvent
 * The animation event gets passed on from the event within the input extender's extension
 * causing the "contentanimation" event.
 */
/**
 * @event aftertoggle
 * Triggered after showExtension or hideExtension has been called. At this point extension() will
 * already return the extension's node but the extension might still not be visible for the user
 * since the animation has just been started.
 * @param {jQuery.Event} event
 */
$.widget( 'ui.inputextender', {
	/**
	 * @see jQuery.Widget.options
	 * @protected
	 * @readonly
	 */
	options: {
		content: [],
		contentAnimationEvents: '',
		initCallback: null,
		hideWhenInputEmpty: true,
		position: {
			my: 'left top',
			at: 'left bottom',
			collision: 'none',
			offset: '-4 2'
		}
	},

	/**
	 * The input extension's node. Will be null until the extension is required for the first
	 * time.
	 *
	 * @protected Use extension() instead. extension() will return null if the _$extension is not
	 *            being used. _$extension might be destroyed in that case in future versions, so
	 *            do not rely on it being set all of the time after its first initialization.
	 * @property {jQuery|null}
	 */
	_$extension: null,

	/**
	 * Whether the input extender is in its extended state right now.
	 *
	 * @property {boolean} [_isExtended=false]
	 * @protected
	 */
	_isExtended: false,

	/**
	 * Whether the input extender is visible
	 *
	 * @property {boolean} [_extensionIsVisible=false]
	 * @protected
	 */
	_extensionIsVisible: false,

	/**
	 * Caches the timeout when the actual input extender animation should kick in.
	 *
	 * @property {number}
	 * @protected
	 */
	_animationTimeout: null,

	/**
	 * Caches the element's offset to determine whether the input extension has to be
	 * repositioned on draw() calls.
	 *
	 * @property {Object}
	 * @protected
	 */
	_offset: null,

	/**
	 * @see jQuery.Widget._create
	 * @protected
	 */
	_create: function() {
		var self = this;

		this.element.addClass( this.widgetBaseClass + '-input' );

		// TODO: focus per mouse and tab should be treated differently. Focus by tab should
		//  leave enough time to tab again, by mouse the extension can be shown immediately.
		this.element
		.on( 'focus.' + this.widgetName, function( event ) {
			if ( !self.options.hideWhenInputEmpty || self.element.val() !== '' ) {
				clearTimeout( self._animationTimeout );
				self._animationTimeout = setTimeout( function() {
					self.showExtension();
				}, 250 ); // TODO: Fixed values can't be changed nor turned off
			}
		} )
		.on( 'blur.' + this.widgetName, function( event ) {
			if ( self.__extensionFocused ) {
				// The focus went to the extension, don't close
				delete self.__extensionFocused;
				return;
			}
			clearTimeout( self._animationTimeout );
			self._animationTimeout = setTimeout( function() {
				self.hideExtension();
			}, 250 ); // TODO: Fixed values can't be changed nor turned off
		} )
		.on( 'keydown.' + this.widgetName, function( event ) {
			if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
				self.hideExtension();
			} else if ( self.extensionIsVisible()
				&& event.keyCode === $.ui.keyCode.TAB
				&& !event.shiftKey
			) {
				event.preventDefault();
				// When tabbing out of the input element, focus the first focusable element
				// within the extension.
				var $focusable = self._$extension.find( ':focusable' );
				if ( $focusable.length ) {
					$focusable.first().focus();
					clearTimeout( self._animationTimeout );
				}
			}
		} );

		if ( this.options.hideWhenInputEmpty ) {
			this.element.on( 'eachchange', function( event, oldValue ) {
				if ( self.element.val() === '' ) {
					self.hideExtension();
				} else {
					self.showExtension();
				}
			} );
		}

		$( 'html' )
		.off( '.' + this.widgetName )
		// Blurring by clicking away from the widget (one handler is sufficient):
		.on( 'click.' + this.widgetName, function( event ) {
			// Loop through all widgets and hide content when having clicked out of it:
			$( ':' + self.widgetBaseClass ).each( function( i, widgetNode ) {
				var widget = $( widgetNode ).data( self.widgetName ),
					$target = $( event.target );

				// Hide the extension neither it nor the corresponding input element is
				// clicked:
				if ( !$target.closest( widget.element.add( widget._$extension ) ).length ) {
					widget.hideExtension();
				}

			} );
		} );

		if ( this.element.is( ':focus' ) ) {
			this.showExtension();
		} else {
			this.draw();
		}
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		if ( this.extensionIsActive() ) {
			// Hide extension the official way, make sure events getting triggered.
			this.hideExtension();
		}

		if ( this._$extension ) {
			// Stop any ongoing extension hiding animation immediately, jump to its end.
			this._$extension.stop( false, true );
			this._$extension.remove();
			this._$extension = null;
		}

		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Shows the extension.
	 *
	 * @param {Function} [callback] Invoked as soon as the extension's show animation is done.
	 */
	showExtension: function( callback ) {
		if ( !this._isExtended ) {
			this._isExtended = true;
			this.draw( callback );
			this._trigger( 'aftertoggle' );
		}
	},

	/**
	 * Hides the extension.
	 *
	 * @param {Function} [callback] Invoked as soon as the extension's hide animation is done.
	 */
	hideExtension: function( callback ) {
		if ( this._isExtended ) {
			this._isExtended = false;
			this.draw( callback );
			this._trigger( 'aftertoggle' );
		}
	},

	/**
	 * Returns the input extension's node or null in case the extension is currently not in its
	 * visible state.
	 *
	 * @return {jQuery|null}
	 */
	extension: function() {
		return this.extensionIsVisible() ? this._$extension : null;
	},

	/**
	 * Returns whether the extension is currently active.
	 *
	 * @return {boolean}
	 */
	extensionIsActive: function() {
		return this._isExtended;
	},

	/**
	 * Returns whether the extension is currently visible. Will still return true after
	 * hideExtension() got called until the hide animation will be completed.
	 *
	 * @return {boolean}
	 */
	extensionIsVisible: function() {
		if ( !this._$extension ) {
			return false;
		}
		return this._extensionIsVisible;
	},

	/**
	 * Draws the widget.
	 *
	 * @param {Function} [callback] Parameter is for private use only.
	 */
	draw: function( callback ) {
		this.element[ this._isExtended ? 'addClass' : 'removeClass' ](
			this.widgetBaseClass + '-extended' );

		this._drawExtension( callback );
	},

	/**
	 * @protected
	 *
	 * @param {Function} [callback]
	 */
	_drawExtension: function( callback ) {
		var extensionIsVisible = this.extensionIsVisible(),
			$extension = this._$extension;

		if ( !extensionIsVisible && !this._isExtended ) {
			// Extension not displayed and not supposed to be displayed.
			return;
		}

		if ( !$extension ) {
			$extension = this._buildExtension();
			this._$extension = $extension;
			$extension.appendTo( $( 'body' ) );

			if ( typeof this.options.initCallback === 'function' ) {
				$extension.show();
				this.options.initCallback.call( this, $extension );
				$extension.hide();
			}
		}

		// Element needs to be visible to use jquery.ui.position.
		if ( !extensionIsVisible ) {
			$extension.show();
			this._reposition();
			$extension.hide();
		} else {
			this._reposition();
		}

		if ( extensionIsVisible !== this._isExtended ) {
			// Represent actual expansion status:
			if ( this._isExtended ) {
				this._drawExtensionExpansion( callback );
			} else {
				this._drawExtensionRemoval( callback );
			}
		}
	},

	/**
	 * @protected
	 *
	 * @param {Function} callback
	 */
	_drawExtensionExpansion: function( callback ) {
		var self = this;
		this._extensionIsVisible = true;

		// When blurring the browser viewport and an re-focusing, Chrome is firing the "focus"
		// event twice. jQuery fadeIn sets the opacity to 0 for the first fadeIn but does not
		// pick up the value when triggering fadeIn the second time.
		if ( this._$extension.css( 'opacity' ) === '0' ) {
			this._$extension.css( 'opacity', '1' );
		}

		this._$extension.stop( true ).animateWithEvent(
			'extensionexpansion',
			'fadeIn',
			{
				duration: 100,
				complete: function() {
					if ( typeof callback === 'function' ) {
						callback();
					}
				}
			},
			function( animationEvent ) {
				self._trigger( 'animation', animationEvent );
			}
		);
		inputExtendersWithVisibleExtension.add( this );
	},

	/**
	 * @protected
	 *
	 * @param {Function} [callback]
	 */
	_drawExtensionRemoval: function( callback ) {
		var self = this;
		this._extensionIsVisible = false;

		this._$extension.stop( true ).animateWithEvent(
			'extensionremoval',
			'fadeOut',
			{
				duration: 100,
				complete: function() {
					inputExtendersWithVisibleExtension.remove( self );
					if ( typeof callback === 'function' ) {
						callback();
					}
				}
			},
			function( animationEvent ) {
				self._trigger( 'animation', animationEvent );
			}
		);
	},

	/**
	 * Repositions the extension.
	 *
	 * @protected
	 */
	_reposition: function() {
		var offset = this.element.offset(),
			vOffsetChanged = this._offset && offset.top !== this._offset.top,
			hOffsetChanged = this._offset && ( offset.left !== this._offset.left
				|| IS_RTL && offset.right !== this._offset.right );

		if ( this._offset && ( vOffsetChanged || hOffsetChanged ) ) {
			return; // Position has not changed.
		}

		/**
		 * Flips the position string in RTL context.
		 *
		 * @param {string} string
		 * @return {string}
		 */
		function evaluateRtl( string ) {
			if ( IS_RTL ) {
				string = ( string.indexOf( 'left' ) !== -1 )
					? string.replace( /left/ig, 'right' )
					: string.replace( /right/ig, 'left' );
			}
			return string;
		}

		var positionParams = {
			of: this.element,
			my: evaluateRtl( this.options.position.my ),
			at: evaluateRtl( this.options.position.at )
		};

		// TODO: Repositioning is not optimal in RTL context when hitting the toggler in the
		//  extension to hide additional input. This seems to be caused by a width
		//  miscalculation which can be debugged with "console.log( this._$extension.width() )".
		this._$extension.position( $.extend( {}, this.options.position, positionParams ) );

		this._offset = offset;
	},

	/**
	 * @protected
	 *
	 * @return {jQuery}
	 */
	_buildExtension: function() {
		var self = this;
		var $closeButton = this._buildExtensionCloseButton();
		var $extension = $( '<div/>', {
			class: this.widgetBaseClass + '-extension ui-widget-content'
		} );
		var focusToExtension = function() {
			// Indicate that the extension is focused now and, if needed, stop the hiding of the preview.
			self.__extensionFocused = true;
			clearTimeout( self._animationTimeout );
			self.showExtension();
		};

		$closeButton.append( this.options.content );

		$extension
		.append( $closeButton )
		.on( 'mousedown.' + this.widgetName, function( event ) {
			if ( !$( event.target ).closest( $closeButton ).length ) {
				focusToExtension();
			}
		} )
		.on( 'focusin.' + this.widgetName, function( event ) {
			focusToExtension();
		} )
		.on( this.options.contentAnimationEvents, function( animationEvent ) {
			self._reposition();
			self._trigger( 'contentanimation', animationEvent );
		} )
		.on( 'keydown.' + this.widgetName, function( event ) {
			// Take care of tabbing out of the extension again:
			if ( event.keyCode === $.ui.keyCode.TAB ) {
				var $focusable = self._$extension.find( ':focusable' );

				if ( $focusable.first().is( event.target ) && event.shiftKey ) {
					event.preventDefault();
					// Tab back to the input element:
					self.element.focus();
				} else if ( $focusable.last().is( event.target ) && !event.shiftKey ) {
					event.preventDefault();
					// Tabbing forward out of the extension: Focus the next focusable element
					// after the input element.
					$focusable = $( ':focusable' );
					$focusable.each( function( i, node ) {
						if ( self.element.is( node ) ) {
							self.hideExtension();
							$focusable[ ( i + 1 >= $focusable.length ) ? 0 : i + 1 ].focus();
						}
					} );
				}
			}
		} );
		return $extension;
	},

	/**
	 * @protected
	 *
	 * @return {jQuery}
	 */
	_buildExtensionCloseButton: function() {
		var self = this,
			$closeButton = $( '<div/>' ),
			$closeIcon = $( '<div/>' ).addClass( 'ui-icon ui-icon-close' );

		$closeButton.addClass( this.widgetBaseClass + '-extension-close ui-state-default' );

		$closeButton.on( 'mouseover.' + this.widgetName, function( event ) {
			$( this ).addClass( 'ui-state-hover' );
		} )
		.on( 'mouseout.' + this.widgetName, function( event ) {
			$( this ).removeClass( 'ui-state-hover' );
		} )
		.on( 'click.' + this.widgetName, function( event ) {
			self.hideExtension();
		} )
		.append( $closeIcon );

		return $closeButton;
	}
} );

/**
 * Returns all the widget instances with currently visible extensions.
 *
 * @member jQuery.ui.inputextender
 * @method getInstancesWithVisibleExtensions
 * @static
 *
 * @return {jQuery.ui.inputextender[]}
 */
$.ui.inputextender.getInstancesWithVisibleExtensions = function() {
	return inputExtendersWithVisibleExtension.get();
};

/**
 * Will redraw all currently visible extensions of all input extender instances.
 * This is useful when changing the DOM, making sure that extensions are still next to their
 * input boxes in case position of the input boxes has changed.
 *
 * @member jQuery.ui.inputextender
 * @method redrawVisibleExtensions
 * @static
 */
$.ui.inputextender.redrawVisibleExtensions = function() {
	$.each( $.ui.inputextender.getInstancesWithVisibleExtensions(), function( i, instance ) {
		instance.draw();
	} );
};

}() );
