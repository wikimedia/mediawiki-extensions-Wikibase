/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

var PARENT = $.wikibase.toolbar;

/**
 * "Edit" toolbar widget.
 * @extends jQuery.wikibase.toolbar
 * @since 0.4
 *
 * This widget offers a "edit" toolbar allowing editing-related interaction with a specific widget.
 * The widget the toolbar shall interact with has to have implemented certain methods listed in
 * the _requiredMethods attribute.
 * Apart from the required methods, the interaction widget has to have defined a help message in
 * its options that will be used as tooltip message.
 *
 * @option {jQuery.Widget} interactionWidget
 *         Name of the widget the toolbar shall interact with.
 *
 * @option {Function} [onRemove]
 *         Function to be triggered when hitting the "remove" button. If omitted, no "remove"
 *         button will be shown.
 *         Default: null
 *
 * @event edit
 *        Triggered after the "edit" button is hit and the interaction widget has switched to edit
 *        mode.
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *        Triggered after the interaction widget and toolbar has switched to edit mode.
 *        - {jQuery.Event}
 *
 * @event afterstopediting
 *        Triggered after the interaction widget and the toolbar has switched to non-edit mode.
 *        - {jQuery.Event}
 */
$.widget( 'wikibase.edittoolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.toolbar.options
	 */
	options: {
		interactionWidget: null,
		onRemove: null,
		buttonLabels: {
			edit: mw.msg( 'wikibase-edit' ),
			save: mw.msg( 'wikibase-save' ),
			remove: mw.msg( 'wikibase-remove' ),
			cancel: mw.msg( 'wikibase-cancel' )
		},
		animationOptions: {
			duration: 'fast'
		}
	},

	/**
	 * Names of methods that are required in the interaction widget to ensure proper toolbar
	 * interaction.
	 * @type {string[]}
	 */
	_requiredMethods: [
		'startEditing',
		'stopEditing',
		'setError'
	],

	/**
	 * @type {Object}
	 */
	_buttons: null,

	/**
	 * Node holding the tooltip image with the tooltip itself attached.
	 * @type {null|jQuery}
	 */
	_$tooltipAnchor: null,

	/**
	 * @see jQuery.wikibase.toolbar._create
	 */
	_create: function() {
		if( !this.options.interactionWidget ) {
			throw new Error( 'Interaction widget needs to be defined' );
		}

		PARENT.prototype._create.call( this );

		if( !this.options.interactionWidget.option( 'helpMessage' ) ) {
			throw new Error( 'Interaction widget help message missing' );
		}

		var missingMethods = this.checkRequiredMethods();
		if( missingMethods.length ) {
			throw new Error( 'Required method(s) missing: ' + missingMethods.join( ', ' ) );
		}

		this._buttons = {};

		var $scrapedSubToolbar = this._getContainer().children( '.wikibase-toolbar' );

		this._initSubToolbar( $scrapedSubToolbar );
		this._attachEventHandlers();

		if( $scrapedSubToolbar.length ) {
			this.toNonEditMode();
		} else {
			this._toNonEditMode();
		}
	},

	/**
	 * @see jQuery.wikibase.toolbar.destroy
	 */
	destroy: function() {
		var self = this;

		this.options.interactionWidget.element.off( '.' + this.widgetName );

		if( this._$tooltipAnchor ) {
			var $wbtooltip = this._$tooltipAnchor.find( ':wikibase-wbtooltip' ),
				wbtooltip = $wbtooltip.data( 'wbtooltip' );
			if( wbtooltip ) {
				wbtooltip.destroy();
			}

			this._$tooltipAnchor.data( 'wikibase-toolbaritem' ).destroy();
		}

		this._getContainer().off( '.' + this.widgetName );

		$.each( this._buttons, function( buttonName, $button ) {
			$button.off( '.' + self.widgetName );
			wbtooltip = $button.data( 'wbtooltip' );
			if( wbtooltip ) {
				wbtooltip.destroy();
			}
			$button.data( 'wikibase-toolbarbutton' ).destroy();
		} );

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Checks whether all methods required in the interaction widget are defined and will return
	 * the names of any missing methods.
	 *
	 * @return {string[]}
	 */
	checkRequiredMethods: function() {
		var self = this,
			missingMethods = [];
		$.each( this._requiredMethods, function( i, methodName ) {
			if( !$.isFunction( self.options.interactionWidget[methodName] ) ) {
				missingMethods.push( methodName );
			}
		} );
		return missingMethods;
	},

	/**
	 * Initializes the sub toolbar encapsulating the toolbar buttons excluding the tooltip anchor.
	 *
	 * @param {jQuery} $subToolbar
	 */
	_initSubToolbar: function( $subToolbar ) {
		var $content = $();

		if( !$subToolbar.length ) {
			$subToolbar = $( '<span/>' ).appendTo( this._getContainer() );
		} else {
			this._scrapeButtons( $subToolbar );
			$content = $subToolbar.children();
		}

		$subToolbar.toolbar( {
			$content: $content,
			renderItemSeparators: true
		} );
	},

	/**
	 * Analyzes a DOM structure in order to detect and reuse button nodes.
	 *
	 * @param {jQuery} $subToolbar
	 */
	_scrapeButtons: function( $subToolbar ) {
		var self = this;

		$subToolbar.children( '.wikibase-toolbar-button' ).each( function() {
			var $button = $( this );
			$.each( self.options.buttonLabels, function( buttonName, label ) {
				if( $button.text() === label ) {
					self._buttons[buttonName] = $button.toolbarbutton( {
						$label: self.options.buttonLabels[buttonName]
					} );
				}
			} );
		} );
	},

	_attachEventHandlers: function() {
		var self = this,
			prefix = this.options.interactionWidget.widgetEventPrefix;

		function isInteractionWidgetNode( node ) {
			return node === self.options.interactionWidget.element.get( 0 );
		}

		this.options.interactionWidget.element
		.on( prefix + 'afterstartediting.' + this.widgetName, function( event ) {
			if( isInteractionWidgetNode( event.target ) ) {
				self.toEditMode();
				self._trigger( 'afterstartediting' );
			}
		} )
		.on( prefix + 'stopediting.' + this.widgetName, function( event, dropValue ) {
			if( !isInteractionWidgetNode( event.target ) ){
				return;
			}
			self.disable();
			if( !dropValue ) {
				self.toggleActionMessage( mw.msg( 'wikibase-save-inprogress' ) );
			}
		} )
		.on( prefix + 'afterstopediting.' + this.widgetName, function( event, dropValue ) {
			if( isInteractionWidgetNode( event.target ) ) {
				self.toNonEditMode();
				self.enable();
				if( !dropValue ) {
					self.toggleActionMessage( function() {
						self._trigger( 'afterstopediting' );
					} );
				}
			}
		} )
		.on( prefix + 'disable.' + this.widgetName, function( event, disable ) {
			if( isInteractionWidgetNode( event.target ) ) {
				self[disable ? 'disable' : 'enable']();
			}
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			if( isInteractionWidgetNode( event.target ) && error instanceof wb.RepoApiError ) {
				var $anchor;

				if( error.action === 'save' ) {
					$anchor = self.getButton( 'save' ).element;
				} else if( error.action === 'remove' ) {
					$anchor = self.getButton( 'remove' ).element;
				}

				self.enable();
				self.toggleActionMessage( function() {
					self.displayError( error, $anchor );
				} );
			}
		} );

		this._getContainer()
		.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			if( self._buttons.edit && event.target === self._buttons.edit.get( 0 ) ) {
				self.options.interactionWidget.element.one(
					prefix + 'afterstartediting.' + self.widgetName,
					function() {
						self._trigger( 'edit' );
					}
				);
				self.options.interactionWidget.startEditing();
			} else if( self._buttons.save && event.target === self._buttons.save.get( 0 ) ) {
				self.options.interactionWidget.stopEditing();
			} else if( self._buttons.remove && event.target === self._buttons.remove.get( 0 ) ) {
				self.disable();
				self.toggleActionMessage( mw.msg( 'wikibase-remove-inprogress' ) );
				self.options.onRemove();
			} else if( self._buttons.cancel && event.target === self._buttons.cancel.get( 0 ) ) {
				self.options.interactionWidget.cancelEditing();
			}
		} );
	},

	/**
	 * Switches the toolbar to edit mode displaying "save", "cancel" and - depending on the toolbar
	 * configuration - "remove" buttons.
	 */
	toEditMode: function() {
		if( this._isInEditMode() ) {
			return;
		}

		var $subToolbar = this._getContainer().children( ':wikibase-toolbar' ),
			subToolbar = $subToolbar.data( 'toolbar' );

		var $buttons = this.getButton( 'save' ).element;
		if( $.isFunction( this.options.onRemove ) ) {
			$buttons = $buttons.add( this.getButton( 'remove' ).element );
		}
		$buttons = $buttons.add( this.getButton( 'cancel' ).element );
		subToolbar.option( '$content', $buttons );

		this._getContainer()
		.append( this._getTooltipAnchor() )
		.addClass( this.widgetBaseClass + '-ineditmode' );
	},

	/**
	 * Forces drawing edit mode.
	 */
	_toEditMode: function() {
		this._getContainer().removeClass( this.widgetBaseClass + '-ineditmode' );
		this.toEditMode();
	},

	/**
	 * Switches the toolbar to non-edit mode displaying the "edit" button.
	 */
	toNonEditMode: function() {
		if( !this._isInEditMode() ) {
			return;
		}

		if( this._$tooltipAnchor ) {
			this._$tooltipAnchor.detach();
		}

		var $subToolbar = this._getContainer().children( ':wikibase-toolbar' ),
			subToolbar = $subToolbar.data( 'toolbar' );

		subToolbar.option( '$content', this.getButton( 'edit' ).element );

		this._getContainer().removeClass( this.widgetBaseClass + '-ineditmode' );
	},

	/**
	 * Forces drawing non-edit mode.
	 */
	_toNonEditMode: function() {
		this._getContainer().addClass( this.widgetBaseClass + '-ineditmode' );
		this.toNonEditMode();
	},

	/**
	 * @return {boolean}
	 */
	_isInEditMode: function() {
		return this._getContainer().hasClass( this.widgetBaseClass + '-ineditmode' );
	},

	/**
	 * @return {jQuery}
	 */
	_getTooltipAnchor: function() {
		var self = this;

		if( this._$tooltipAnchor ) {
			return this._$tooltipAnchor;
		}

		this._$tooltipAnchor = $( '<span/>', {
			'class': 'mw-help-field-hint',
			style: 'display:inline-block;text-decoration:none;width:8px;', // TODO: Get rid of inline styles.
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
		} ).toolbaritem();

		// Support promises instead of strings, too, since $.wikibase.claimview does not know
		// immediately after creation which help message to show.
		// TODO: This should be replaced by a dynamic getter so that views can arbitrarily
		// change their help messages anywhere in their lifecycle.
		function addTooltip( helpMessage ) {
			if( self._$tooltipAnchor ) {
				self._$tooltipAnchor.wbtooltip( {
					content: helpMessage
				} );
			}
		}

		var helpMessage = this.options.interactionWidget.option( 'helpMessage' );
		if( helpMessage.done && typeof helpMessage !== 'string' ) {
			helpMessage.done( addTooltip );
		} else {
			addTooltip( helpMessage );
		}

		return this._$tooltipAnchor;
	},

	/**
	 * Returns a button by its name creating the button if it has not yet been created.
	 *
	 * @param {string} buttonName "edit"|"save"|"remove"|"cancel"
	 * @return {jQuery.wikibase.toolbarbutton}
	 */
	getButton: function( buttonName ) {
		if( !this._buttons[buttonName] ) {
			this._buttons[buttonName] = $( '<span/>' ).toolbarbutton( {
				$label: this.options.buttonLabels[buttonName],
				cssClassSuffix: buttonName
			} );
		}

		return this._buttons[buttonName].data( 'toolbarbutton' );
	},

	/**
	 * Toggles a message replacing the toolbar contents.
	 *
	 * @param {string|Function} [messageOrCallback] Message to be displayed instead of the
	 *        toolbar contents or callback that is supposed to be triggered after removing the
	 *        replacement message again.
	 */
	toggleActionMessage: function( messageOrCallback ) {
		var self = this,
			$container = this._getContainer(),
			actionMessageClass = this.widgetBaseClass + '-actionmsg',
			$actionMsg = $container.find( '.' + actionMessageClass );

		messageOrCallback = messageOrCallback || function() {};

		if( $.isFunction( messageOrCallback ) ) {
			if( !$actionMsg.length ) {
				messageOrCallback();
			} else {
				$actionMsg.stop()
				.fadeOut( this.options.animationOptions )
				.promise().done( function() {
					$actionMsg.remove();
					$container.contents()
					.fadeIn( self.options.animationOptions )
					.promise().done( function() {
						messageOrCallback();
					} );
				} );
			}
		} else {
			$container.contents().hide();

			$actionMsg = $( '<span/>' )
				.addClass( actionMessageClass + ' wb-actionmsg' )
				.text( messageOrCallback )
				.appendTo( $container )
				.hide()
				.fadeIn( this.options.animationOptions );
		}
	},

	/**
	 * Displays an error message.
	 *
	 * @param {wikibase.RepoApiError} error
	 * @param {jQuery} $anchor Node the tooltip shall be attached to.
	 */
	displayError: function( error, $anchor ) {
		var self = this;

		$anchor
		.wbtooltip( {
			content: error,
			permanent: true
		} )
		.one( 'wbtooltipafterhide.' + this.widgetName, function() {
			self.options.interactionWidget.setError();
			var wbtooltip = $anchor.data( 'wbtooltip' );
			if( wbtooltip ) {
				wbtooltip.destroy();
			}
		} );

		$anchor.data( 'wbtooltip' ).show();
	},

	/**
	 * @see jQuery.wikibase.toolbar._setOption
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'onRemove' && this._isInEditMode() ) {
			this._toEditMode();
		}

		return response;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
