/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	require( './jquery.wikibase.toolbar.js' );
	require( './jquery.wikibase.toolbarbutton.js' );

	var PARENT = $.wikibase.toolbar;

	/**
	 * "Edit" toolbar widget.
	 *
	 * @extends jQuery.wikibase.toolbar
	 *
	 * This widget offers a "edit" toolbar allowing editing-related interaction with a specific widget.
	 * The widget the toolbar shall interact with has to have implemented certain methods listed in
	 * the _requiredMethods attribute.
	 * Apart from the required methods, the interaction widget has to have defined a help message in
	 * its options that will be used as tooltip message.
	 *
	 * @option {Function} getHelpMessage
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
			getHelpMessage: null,
			onRemove: null,
			buttonLabels: {
				edit: mw.msg( 'wikibase-edit' ),
				save: mw.msg( mw.config.get( 'wgEditSubmitButtonLabelPublish' ) ? 'wikibase-publish' : 'wikibase-save' ),
				remove: mw.msg( 'wikibase-remove' ),
				cancel: mw.msg( 'wikibase-cancel' )
			}
		},

		/**
		 * Names of methods that are required in the interaction widget to ensure proper toolbar
		 * interaction.
		 *
		 * @type {string[]}
		 */
		_requiredMethods: [
			'cancelEditing',
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
		 *
		 * @type {null|jQuery}
		 */
		_$tooltipAnchor: null,

		/**
		 * @property {jQuery.ui.EditableTemplatedWidget|wikibase.view.ViewController} [controller]
		 * @private
		 */
		_controller: null,

		/**
		 * @see jQuery.wikibase.toolbar._create
		 */
		_create: function () {
			PARENT.prototype._create.call( this );

			if ( !this.options.getHelpMessage ) {
				throw new Error( 'Required option not specified properly' );
			}

			this._buttons = {};

			var $scrapedSubToolbar = this.getContainer().children( '.wikibase-toolbar' );

			this._initSubToolbar( $scrapedSubToolbar );
			this._attachEventHandlers();

			if ( $scrapedSubToolbar.length && $scrapedSubToolbar.children().length ) {
				this.toNonEditMode();
			} else {
				this._toNonEditMode();
			}
		},

		setController: function ( controller ) {
			this._controller = controller;
			var missingMethods = this.checkRequiredMethods();
			if ( missingMethods.length ) {
				throw new Error( 'Required method(s) missing: ' + missingMethods.join( ', ' ) );
			}
		},

		/**
		 * @see jQuery.wikibase.toolbar.destroy
		 */
		destroy: function () {
			var self = this;

			if ( this._$tooltipAnchor ) {
				var $wbtooltip = this._$tooltipAnchor.find( ':wikibase-wbtooltip' ),
					wbtooltip = $wbtooltip.data( 'wbtooltip' );
				if ( wbtooltip ) {
					wbtooltip.destroy();
				}

				this._$tooltipAnchor.data( 'wikibase-toolbaritem' ).destroy();
			}

			this.getContainer().off( '.' + this.widgetName );

			// eslint-disable-next-line no-jquery/no-each-util
			$.each( this._buttons, function ( buttonName, $button ) {
				$button.off( '.' + self.widgetName );
				var buttonWbtooltip = $button.data( 'wbtooltip' );
				if ( buttonWbtooltip ) {
					buttonWbtooltip.destroy();
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
		checkRequiredMethods: function () {
			var self = this,
				missingMethods = [];
			this._requiredMethods.forEach( function ( methodName ) {
				if ( typeof self._controller[ methodName ] !== 'function' ) {
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
		_initSubToolbar: function ( $subToolbar ) {
			var $content = $();

			if ( !$subToolbar.length ) {
				$subToolbar = $( '<span>' ).appendTo( this.getContainer() );
			} else {
				this._scrapeButtons( $subToolbar );
				$content = $subToolbar.children();
			}

			$subToolbar.toolbar( {
				$content: $content
			} );
		},

		/**
		 * Analyzes a DOM structure in order to detect and reuse button nodes.
		 *
		 * @param {jQuery} $subToolbar
		 */
		_scrapeButtons: function ( $subToolbar ) {
			var self = this;

			$subToolbar.children( '.wikibase-toolbar-button' ).each( function () {
				var $button = $( this );
				// eslint-disable-next-line no-jquery/no-each-util
				$.each( self.options.buttonLabels, function ( buttonName, label ) {
					if ( $button.text() === label ) {
						self._buttons[ buttonName ] = $button.toolbarbutton( {
							$label: self.options.buttonLabels[ buttonName ]
						} );
					}
				} );
			} );
		},

		_attachEventHandlers: function () {
			var self = this;

			this.getContainer()
			.on( 'toolbarbuttonaction.' + this.widgetName, function ( event ) {
				if ( self._buttons.edit && event.target === self._buttons.edit.get( 0 ) ) {
					self._controller.startEditing();
				} else if ( self._buttons.save && event.target === self._buttons.save.get( 0 ) ) {
					self._controller.stopEditing();
				} else if ( self._buttons.remove && event.target === self._buttons.remove.get( 0 ) ) {
					self.disable();
					self.toggleActionMessage( mw.msg( 'wikibase-remove-inprogress' ) );
					self.options.onRemove();
				} else if ( self._buttons.cancel && event.target === self._buttons.cancel.get( 0 ) ) {
					self._controller.cancelEditing();
				}
			} );
		},

		/**
		 * Switches the toolbar to edit mode displaying "save", "cancel" and - depending on the toolbar
		 * configuration - "remove" buttons.
		 */
		toEditMode: function () {
			if ( this._isInEditMode() ) {
				return;
			}

			var $subToolbar = this.getContainer().children( ':wikibase-toolbar' ),
				subToolbar = $subToolbar.data( 'toolbar' );

			// This may happen while "Saving..." is shown.
			if ( !subToolbar ) {
				return;
			}

			var $buttons = this.getButton( 'save' ).element;
			if ( typeof this.options.onRemove === 'function' ) {
				$buttons = $buttons.add( this.getButton( 'remove' ).element );
			}
			$buttons = $buttons.add( this.getButton( 'cancel' ).element );
			subToolbar.option( '$content', $buttons );

			this.getContainer()
			.append( this._getTooltipAnchor() )
			.addClass( this.widgetBaseClass + '-ineditmode' );
		},

		/**
		 * Forces drawing edit mode.
		 */
		_toEditMode: function () {
			this.getContainer().removeClass( this.widgetBaseClass + '-ineditmode' );
			this.toEditMode();
		},

		/**
		 * Switches the toolbar to non-edit mode displaying the "edit" button.
		 */
		toNonEditMode: function () {
			if ( !this._isInEditMode() ) {
				return;
			}

			if ( this._$tooltipAnchor ) {
				this._$tooltipAnchor.detach();
			}

			var $subToolbar = this.getContainer().children( ':wikibase-toolbar' ),
				subToolbar = $subToolbar.data( 'toolbar' );

			// This may happen while "Saving..." is shown.
			if ( !subToolbar ) {
				return;
			}

			subToolbar.option( '$content', this.getButton( 'edit' ).element );

			this.getContainer().removeClass( this.widgetBaseClass + '-ineditmode' );
		},

		/**
		 * Forces drawing non-edit mode.
		 */
		_toNonEditMode: function () {
			this.getContainer().addClass( this.widgetBaseClass + '-ineditmode' );
			this.toNonEditMode();
		},

		/**
		 * @return {boolean}
		 */
		_isInEditMode: function () {
			return this.getContainer().hasClass( this.widgetBaseClass + '-ineditmode' );
		},

		/**
		 * @return {jQuery}
		 */
		_getTooltipAnchor: function () {
			var self = this;

			if ( this._$tooltipAnchor ) {
				return this._$tooltipAnchor;
			}

			this._$tooltipAnchor = $( '<span>' )
				.addClass( 'wb-help-field-hint' )
				.text( '\u00A0' ) // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
				.toolbaritem();

			// Support promises instead of strings, too, since $.wikibase.statementview does not know
			// immediately after creation which help message to show.
			// TODO: This should be replaced by a dynamic getter so that views can arbitrarily
			// change their help messages anywhere in their lifecycle.
			function addTooltip( helpMessage ) {
				if ( self._$tooltipAnchor ) {
					if ( helpMessage ) {
						self._$tooltipAnchor.wbtooltip( { content: helpMessage } );
					} else {
						self._$tooltipAnchor.hide();
					}
				}
			}

			this.options.getHelpMessage().done( addTooltip );

			return this._$tooltipAnchor;
		},

		/**
		 * Returns a button by its name creating the button if it has not yet been created.
		 *
		 * @param {string} buttonName "edit"|"save"|"remove"|"cancel"
		 * @return {jQuery.wikibase.toolbarbutton}
		 */
		getButton: function ( buttonName ) {
			if ( !this._buttons[ buttonName ] ) {
				this._buttons[ buttonName ] = $( '<span>' ).toolbarbutton( {
					$label: this.options.buttonLabels[ buttonName ],
					cssClassSuffix: buttonName
				} );
			}

			return this._buttons[ buttonName ].data( 'toolbarbutton' );
		},

		/**
		 * Toggles a message replacing the toolbar contents.
		 *
		 * @param {string} [message] Message to be displayed instead of the
		 *        toolbar contents. If omitted, the toolbar contents will be shown.
		 */
		toggleActionMessage: function ( message ) {
			var $container = this.getContainer(),
				actionMessageClass = this.widgetBaseClass + '-actionmsg',
				$actionMsg = $container.find( '.' + actionMessageClass );

			if ( message !== undefined ) {
				$container.contents().hide();

				$actionMsg = $( '<span>' )
					.addClass( actionMessageClass + ' wb-actionmsg' )
					.text( message )
					.appendTo( $container );
			} else if ( $actionMsg.length ) {
				$actionMsg.remove();
				$container.contents().show();
			}
		},

		/**
		 * Displays an error message.
		 *
		 * @param {wikibase.api.RepoApiError} error
		 * @param {jQuery} $anchor Node the tooltip shall be attached to.
		 */
		displayError: function ( error, $anchor ) {
			var self = this;

			$anchor
			.wbtooltip( {
				content: error,
				permanent: true
			} )
			.one( 'wbtooltipafterhide.' + this.widgetName, function () {
				self._controller.setError();
				var wbtooltip = $anchor.data( 'wbtooltip' );
				if ( wbtooltip ) {
					wbtooltip.destroy();
				}
			} );

			$anchor.data( 'wbtooltip' ).show();
		},

		/**
		 * @see jQuery.wikibase.toolbar._setOption
		 */
		_setOption: function ( key, value ) {
			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'onRemove' && this._isInEditMode() ) {
				this._toEditMode();
			}

			return response;
		},

		/**
		 * @see jQuery.wikibase.toolbar.focus
		 */
		focus: function () {
			if ( this._isInEditMode() ) {
				var btnSave = this._buttons.save && this._buttons.save.data( 'toolbarbutton' ),
					btnCancel = this._buttons.cancel && this._buttons.cancel.data( 'toolbarbutton' );

				if ( btnSave && !btnSave.option( 'disabled' ) ) {
					btnSave.focus();
					return;
				} else if ( btnCancel && btnCancel.option( 'disabled' ) ) {
					btnCancel.focus();
					return;
				}
			} else {
				var btnEdit = this._buttons.edit && this._buttons.edit.data( 'toolbarbutton' );

				if ( btnEdit && !btnEdit.option( 'disabled' ) ) {
					btnEdit.focus();
					return;
				}
			}

			this.element.trigger( 'focus' );
		}
	} );

}( wikibase ) );
