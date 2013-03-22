/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * "Edit" toolbar widget
	 * @since 0.4
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
	 * @option toolbarParentSelector {string} jQuery selector to find the node the actual toolbar
	 *         buttons shall be appended to. If omitted, the DOM structure required for the toolbar
	 *         will be created and appended to the node the toolbar is initialized on.
	 *
	 * @option enableRemove {boolean} Whether a remove button shall be shown. Regardless of setting
	 *         this options, the "remove" button will not be shown if the interaction object has no
	 *         "remove" method.
	 *         Default value: true
	 */
	$.widget( 'wikibase.edittoolbar', {
		widgetBaseClass: 'wb-edittoolbar',

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			interactionWidgetName: null,
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
			'isValid'
		],

		/**
		 * The widget the toolbar interacts with.
		 * @type {Object}
		 */
		_interactionWidget: null,

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
		 * The edit group object.
		 * @type {wb.ui.Toolbar.EditGroup}
		 */
		editGroup: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this,
				missingMethods;

			if ( !this.options.interactionWidgetName ) {
				throw new Error( 'jquery.wikibase.edittoolbar: Missing interaction widget name' );
			}

			this.$toolbarParent = ( this.options.toolbarParentSelector )
				? this.element.find( this.options.toolbarParentSelector )
				: mw.template( 'wb-toolbar', this.widgetBaseClass, '' ).appendTo( this.element );

			this._interactionWidget = this.element.data( this.options.interactionWidgetName );

			if ( !this._interactionWidget ) {
				throw new Error( 'jquery.wikibase.edittoolbar: Interaction widget not defined' );
			}
			if ( !this._interactionWidget.options.helpMessage ) {
				throw new Error( 'jquery.wikibase.edittoolbar: Missing interaction widget help ' +
					'message' );
			}

			missingMethods = this.checkRequiredMethods();
			if ( missingMethods.length > 0 ) {
				var m = missingMethods.join( ', ' );
				throw new Error( 'jquery.wikibase.edittoolbar: Missing required method(s) ' + m );
			}

			this.toolbar = new wb.ui.Toolbar();

			this.editGroup = new wb.ui.Toolbar.EditGroup( {
				displayRemoveButton: this.options.enableRemove
			} );
			this.toolbar.addElement( this.editGroup );

			this.editGroup.setTooltip( this._interactionWidget.options.helpMessage );

			this.editGroup.on( 'edit', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self._interactionWidget.startEditing();
			} );

			this.editGroup.on( 'cancel', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self._interactionWidget.cancelEditing();
			} );

			this.editGroup.on( 'save', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self._interactionWidget.stopEditing();
			} );

			this.editGroup.on( 'remove', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self.toggleActionMessage( { message: 'wikibase-remove-inprogress' } );
				if ( $.isFunction( self._interactionWidget.remove ) ) {
					self._interactionWidget.remove();
				} else {
					self._interactionWidget._trigger( 'remove' );
				}
			} );

			var prefix = this._interactionWidget.widgetEventPrefix;

			this.element
			.on( prefix + 'afterstartediting', function( event ) {
				self.editGroup.toEditMode();
			} )
			.on( prefix + 'stopediting', function( event, dropValue ) {
				self.disable();
				// Toggling "action message" here in order to react on pressing the "enter" key.
				if ( !dropValue ) {
					self.toggleActionMessage( { message: 'wikibase-save-inprogress' } );
				}
			} )
			.on( prefix + 'afterstopediting', function( event ) {
				self.editGroup.toNonEditMode();
				self.enable();
				self.toggleActionMessage( function() { self.editGroup.btnEdit.setFocus(); } );
			} )
			.on( prefix + 'afterstartediting ' + prefix + 'change', function( event ) {
				var isEmpty = $.isFunction( self._interactionWidget.isEmpty )
					&& self._interactionWidget.isEmpty();

				var isInitial = $.isFunction( self._interactionWidget.isInitialValue )
					&& self._interactionWidget.isInitialValue();

				if ( self._interactionWidget.isValid() && !isEmpty && !isInitial ) {
					self.editGroup.btnSave.enable();
				} else {
					self.editGroup.btnSave.disable();
				}
			} )
			.on( prefix + 'toggleerror', function( event, error ) {
				if ( error && error instanceof wb.RepoApiError ) {
					var anchor;

					if ( error.action === 'save' ) {
						anchor = self.editGroup.btnSave;
					}
					if ( error.action === 'remove' ) {
						anchor = self.editGroup.btnRemove;
					}

					self.enable();
					self.toggleActionMessage( function() {
						self.displayError( error, anchor );
					} );
				}
			} );

			this.toolbar.appendTo(
				$( '<span/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		},

		/**
		 * @see $.widget.destroy
		 */
		destroy: function() {
			this.editGroup.destroy();
			this.toolbar.destroy();
			$.Widget.prototype.destroy.call( this );
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
				self._interactionWidget._trigger( 'toggleerror' );
			} );

			anchor.getTooltip().on( 'afterhide', function( e ) {
				anchor.removeTooltip();
				self.errorTooltipAnchor = null;
			} );

			this.errorTooltipAnchor = anchor;
		}

	} );

}( mediaWiki, wikibase, jQuery ) );
