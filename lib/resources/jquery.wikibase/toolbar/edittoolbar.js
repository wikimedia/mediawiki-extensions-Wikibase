/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.toolbarbase;

	/**
	 * "Edit" toolbar widget
	 * @since 0.4
	 * @extends jQuery.wikibase.toolbarbase
	 *
	 * This widget offers a generic "edit" toolbar which will allow editing-related interaction with
	 * a given widget.
	 * The widget the toolbar shall interact with has to have implemented certain methods listed in
	 * the _requiredMethods attribute. The interaction widget may have an isEmpty() method which
	 * will, in addition to the isValid() method, be queried for en-/disabling the save button.
	 * Apart from the required methods, the interaction widget has to have defined a help message in
	 * its options that will be used as tooltip message.
	 * The toolbar will also trigger a "toggleerror" event on the interaction widget when the error
	 * tooltip that will show up gets hidden.
	 *
	 * @option interactionWidgetName {string} (required) Name of the widget the toolbar shall
	 *         interact with. (That widget needs to be initialized on the same DOM node this toolbar
	 *         is initialized on.)
	 *
	 * @option parentWidgetFullName {string} Name of the interaction widget's parent widget.
	 *         Currently, this is required only when activating "enableRemove".
	 *
	 * @option enableRemove {boolean} Whether a remove button shall be shown. Regardless of setting
	 *         this options, the "remove" button will not be shown if the interaction object has no
	 *         "remove" method.
	 *         Default value: true
	 */
	$.widget( 'wikibase.edittoolbar', PARENT, {
		widgetBaseClass: 'wb-edittoolbar',

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			interactionWidgetName: null,
			parentWidgetFullName: null,
			toolbarParentSelector: null,
			enableRemove: true
		},

		/**
		 * Names of methods that are required in the interaction widget to ensure proper toolbar
		 * interaction.
		 * @type {string[]}
		 */
		_requiredMethods: [
			'startEditing',
			'stopEditing',
			'isValid',
			'setError'
		],

		/**
		 * The widget the toolbar interacts with.
		 * @type {Object}
		 */
		_interactionWidget: null,

		/**
		 * The widget's parent widget (if defined via options).
		 * @type {Object}
		 */
		_parentWidget: null,

		/**
		 * The visible toolbar elements' parent node.
		 * @type {jQuery}
		 */
		$toolbarParent: null,

		/**
		 * The toolbar object.
		 * @type {wb.ui.Toolbar}
		 */
		toolbar: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this,
				missingMethods;

			if ( !this.options.interactionWidgetName ) {
				throw new Error( 'jquery.wikibase.edittoolbar: Missing interaction widget name' );
			}

			PARENT.prototype._create.call( this );

			this._interactionWidget = this.element.data( this.options.interactionWidgetName );

			if ( !this._interactionWidget ) {
				throw new Error( 'jquery.wikibase.edittoolbar: Interaction widget not defined' );
			}
			if ( !this._interactionWidget.options.helpMessage ) {
				throw new Error( 'jquery.wikibase.edittoolbar: Missing interaction widget help ' +
					'message' );
			}

			// Look up the parent widget if defined via options:
			if ( this.options.parentWidgetFullName ) {
				var namespace = this.options.parentWidgetFullName.split( '.' )[ 0 ],
					name = this.options.parentWidgetFullName.split( '.' )[ 1 ],
					prototype = $[ namespace ][ name ].prototype,
					parentWidgetNode = this.element.closest( '.' + prototype.widgetBaseClass );
				this._parentWidget = parentWidgetNode.data( prototype.widgetName );
			}

			if (
				this.options.enableRemove
				&& ( !this._parentWidget || !$.isFunction( this._parentWidget.remove ) )
			) {
				throw new Error( 'jquery.wikibase.edittoolbar: In order to enable remove ' +
					'functionality, a parent widget that covers a "remove" method needs to be ' +
					'specified' );
			}

			missingMethods = this.checkRequiredMethods();
			if ( missingMethods.length > 0 ) {
				var m = missingMethods.join( ', ' );
				throw new Error( 'jquery.wikibase.edittoolbar: Missing required method(s) ' + m );
			}

			this.toolbar = new wb.ui.Toolbar();

			var editGroup = new wb.ui.Toolbar.EditGroup( {
				displayRemoveButton: this.options.enableRemove
			} );
			this.toolbar.addElement( editGroup );

			editGroup.setTooltip( this._interactionWidget.options.helpMessage );

			editGroup.on( 'edit', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self._interactionWidget.startEditing();
			} );

			editGroup.on( 'cancel', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self._interactionWidget.cancelEditing();
			} );

			editGroup.on( 'save', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self._interactionWidget.stopEditing();
			} );

			editGroup.on( 'remove', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self.toggleActionMessage( { message: 'wikibase-remove-inprogress' } );
				self._parentWidget.remove( self._interactionWidget );
			} );

			var prefix = this._interactionWidget.widgetEventPrefix;

			this.element
			.on( prefix + 'afterstartediting', function( event ) {
				editGroup.toEditMode();
			} )
			.on( prefix + 'stopediting', function( event, dropValue ) {
				self.disable();
				// Toggling "action message" here in order to react on pressing the "enter" key.
				if ( !dropValue ) {
					self.toggleActionMessage( { message: 'wikibase-save-inprogress' } );
				}
			} )
			.on( prefix + 'afterstopediting', function( event ) {
				editGroup.toNonEditMode();
				self.enable();
				self.toggleActionMessage( function() {
					editGroup.btnEdit.data( 'toolbarbutton' ).setFocus();
				} );
			} )
			.on( prefix + 'afterstartediting ' + prefix + 'change', function( event ) {
				var isEmpty = $.isFunction( self._interactionWidget.isEmpty )
					&& self._interactionWidget.isEmpty();

				var isInitial = $.isFunction( self._interactionWidget.isInitialValue )
					&& self._interactionWidget.isInitialValue();

				if ( self._interactionWidget.isValid() && !isEmpty && !isInitial ) {
					editGroup.btnSave.data( 'toolbarbutton' ).enable();
				} else {
					editGroup.btnSave.data( 'toolbarbutton' ).disable();
				}
			} )
			.on( prefix + 'toggleerror', function( event, error ) {
				if ( error && error instanceof wb.RepoApiError ) {
					var anchor;

					if ( error.action === 'save' ) {
						anchor = editGroup.btnSave;
					}
					if ( error.action === 'remove' ) {
						anchor = editGroup.btnRemove;
					}

					self.enable();
					self.toggleActionMessage( function() {
						self.displayError( error, anchor.data( 'toolbarbutton' ) );
					} );
				}
			} );

			this.toolbar.editGroup = editGroup;

			this.toolbar.appendTo(
				$( '<span/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		},

		/**
		 * Checks whether all methods required in the interaction widget are defined and will return
		 * the names of any missing methods.
		 * @since 0.4
		 *
		 * @return {string[]}
		 */
		checkRequiredMethods: function() {
			var self = this,
				missingMethods = [];
			$.each( this._requiredMethods, function( i, methodName ) {
				if ( !$.isFunction( self._interactionWidget[methodName] ) ) {
					missingMethods.push( methodName );
				}
			} );
			return missingMethods;
		},

		/**
		 * Toggles the message displayed instead of the toolbar while performing an (API) action
		 * (saving/removing). Omit action parameter to remove any action message and show the
		 * toolbar again.
		 * @since 0.4
		 *
		 * @param {Object|function} [options] String that is used to get the message key. Assumed to
		 *        be a callback if of type function. You may set the following options:
		 *        message:  The message key of the message to display. If not defined, the toolbar
		 *                  will be shown.
		 *        duration: Fade duration in milliseconds (default: 200).
		 * @param {function} [callback] Function to be called after toggling has been finished
		 */
		toggleActionMessage: function( options, callback ) {
			var self = this,
				actionMessageClass = this.widgetBaseClass + '-actionmsg',
				actionMsg = this.$toolbarParent.find( '.' + actionMessageClass );

			if ( options === undefined || $.isFunction( options ) ) {
				if ( $.isFunction( options ) ) {
					callback = options;
				}
				options = {};
			}

			if ( !options.duration ) {
				options.duration = 200; // default fade duration
			}

			if ( !options.message ) { // show toolbar
				if ( !this.$toolbarParent.find( '.' + actionMessageClass ).length ) {
					// If no action message is displayed currently, just trigger the callback.
					if ( $.isFunction( callback ) ) {
						callback();
					}
				} else {
					this.$toolbarParent.find( '.' + actionMessageClass ).stop().fadeToggle(
						options.duration,
						function() {
							self.$toolbarParent.find( '.' + actionMessageClass ).remove();
							self.$toolbarParent.find( '.wb-ui-toolbar' ).fadeIn(
								options.duration,
								function() {
									if ( $.isFunction( callback ) ) {
										callback();
									}
								}
							);
						}
					);
				}
			} else { // show message
				actionMsg = $( '<span/>' )
				.addClass( actionMessageClass + ' wb-actionmsg' )
				.append( $( '<span/>' ).text( mw.msg( options.message ) ) )
				.hide();

				actionMsg.appendTo( this.$toolbarParent.find( '.wb-editsection' ) );

				this.$toolbarParent.find( '.wb-ui-toolbar' ).hide();

				actionMsg.hide().fadeToggle( options.duration, function() {
					if ( $.isFunction( callback ) ) {
						callback();
					}
				} );
			}
		},

		/**
		 * Displays an error message an visualizes the error state.
		 * @since 0.4
		 *
		 * @param {wb.RepoApiError} error
		 * @param {Object} anchor Object which the tooltip shall be attached to.
		 */
		displayError: function( error, anchor ) {
			var self = this;

			anchor.setTooltip( error ).show( true );

			anchor.getTooltip().on( 'hide', function( e ) {
				self.element.removeClass( 'wb-error' ).addClass( 'wb-edit' );
				self._interactionWidget.setError();
			} );

			anchor.getTooltip().on( 'afterhide', function( e ) {
				anchor.removeTooltip();
				self.errorTooltipAnchor = null;
			} );

			this.errorTooltipAnchor = anchor;
		},

		/**
		 * @see $.wikibase.toolbarbase.destroy
		 */
		destroy: function() {
			this.toolbar.editGroup.destroy();
			PARENT.prototype.destroy.call( this );
		}

	} );

}( mediaWiki, wikibase, jQuery ) );
