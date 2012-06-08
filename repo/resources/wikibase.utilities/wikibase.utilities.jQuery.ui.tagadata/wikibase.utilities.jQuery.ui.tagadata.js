/**
 * jQuery UI extension 'tag-a-data'
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * @version 0.1
 *
 * 'tag-a-data' is based on the original jQuery extension 'tag-it' v2.0 (06/2011) by
 *   Levy Carneiro Jr.
 *   Martin Rehfeld
 *   Tobias Schmidt
 *   Skylar Challand
 *   Alex Ehlke
 * See http://aehlke.github.com/tag-it/ for details.
 *
 * Copyright 2011, Levy Carneiro Jr.
 * Released under the MIT license.
 * http://aehlke.github.com/tag-it/LICENSE
 *
 * @since 0.1
 * @file wikibase.utilities.jQuery.ui.tagadata.js
 * @ingroup Wikibase
 *
 * @licence MIT license
 * @author Daniel Werner
 *
 * TODO: Danwe: This should be refactored to introduce a 'Tag' Prototype for representing tags. Right now the whole
 *       thing is a mess made of functions returning/expecting either a label or a DOM node.
 */

( function( $, undefined ) {

	$.widget( 'ui.tagadata', {

		widgetEventPrefix: 'tagadata',

		options: {
			itemName: 'item',
			fieldName: 'tags',
			availableTags: [],

			/**
			 * Defines whether the tags can be altered at all times. If true, the tags contain input boxes so it can
			 * be tabbed over them or clicked inside to alter the value.
			 * @TODO: false for this value not fully supported! There won't be any input at all.
			 * @var Boolean
			 */
			editableTags: true,

			/**
			 * If set to true, hitting backspace will not delete the last tag immediately but highlight it first.
			 * @var Boolean
			 */
			removeConfirmation: false,

			/**
			 * If true, tags with the same text but different capitalization can be inserted.
			 * @var Boolean
			 */
			caseSensitive: true,

			/**
			 * Text used as placeholder in the input field if no text has been typed yet.
			 * @var String
			 */
			placeholderText: null,

			/**
			 * Whether  to animate tag removals or not.
			 * @var Boolean
			 */
			animate: true,

			/**
			 * Keys which - when pressed in the input area - will trigger the current
			 * input to be added as tag. $.ui.keyCode members can be used for convenience.
			 * @var Number[]
			 */
			triggerKeys: [
				$.ui.keyCode.ENTER
			],

			// Event callbacks.
			tagAdded: null,
			beforeTagRemoved: null,
			tagRemoved: null,
			tagChanged: null,
			tagClicked: null
		},


		_create: function() {
			// for handling static scoping inside callbacks
			var self = this;

			this.tagList = this.element.find( 'ul, ol' ).andSelf().last();
			this.originalTags = [];

			this.tagList
			.addClass( 'tagadata' )
			.addClass( 'ui-widget ui-widget-content ui-corner-all' )
			.on( 'click.tagadata', function( e ) {
				var target = $( e.target );
				if( target.hasClass( 'tagadata-label' ) ) {
					self._trigger( 'tagClicked', e, target.closest( '.tagadata-choice' ) );
				}
			} );

			// Add existing tags from the list, if any
			this.tagList.children( 'li' ).each( function() {
				var newTag = self.createTag( $( this ).html(), $( this ).attr( 'class' ) );
				self.originalTags.push( self.getTagLabel( newTag ) );
				$( this ).remove();
			} );

			// create an empty input tag at the end:
			this.getHelperTag();
		},

		_lastTag: function() {
			return this.tagList.children( '.tagadata-choice:last' );
		},

		/**
		 * Returns the nodes of all Tags currently assigned. To get the actual text, use getTagLabel() on them.
		 * If there is an empty tag for inserting a new tag, this won't be returned by this. Use getHelperTag() instead.
		 * If tags have a conflict (same tag exists twice in the list) only one DOM node in the result will represent
		 * all of these conflicted tags.
		 *
		 * @return jQuery[]
		 */
		getTags: function() {
			// Returns an array of tag string values
			var self = this;
			var tags = [];
			var usedLabels = [];

			this.tagList.children( '.tagadata-choice' ).each( function() {
				// check if already removed but still assigned till animations end. if so, don't add tag!
				if( !$( this ).hasClass( 'tagadata-choice-removed' ) ) {
					var tagLabel = self.getTagLabel( this );

					if( tagLabel !== '' // don't want the empty helper tag...
						&& $.inArray( tagLabel, usedLabels ) < 0 // ... or anything twice (in case of conflicts)
					) {
						tags.push( this );
						usedLabels.push( tagLabel );
					}
				}
			} );
			return tags;
		},

		/**
		 * Returns a tags element by its label. If the tag is not in the list, null will be returned.
		 *
		 * @param string label
		 * @return jQuery|null
		 */
		getTag: function( label ) {
			var self = this;
			var result = null;
			this.tagList.children( '.tagadata-choice' ).each( function( i ) {
				if( self._formatLabel( label ) === self._formatLabel( self.getTagLabel( this ) ) ) {
					result = $( this );
					return false;
				}
			} );
			return result;
		},

		/**
		 * Helper function to return all tags having the same value currently
		 *
		 * @param String label
		 * @return jQuery
		 */
		_getTags: function( label ) {
			var self = this;
			label = this._formatLabel( label );

			return this.tagList.children( '.tagadata-choice' ).filter( function() {
				return self.getTagLabel( this ) === label;
			} );
		},

		/**
		 * Returns the label of a tag represented by a DOM node.
		 *
		 * @param jQuery tag
		 * @return string
		 */
		getTagLabel: function( tag ) {
			// Returns the tag's string label (input can be direct child or inside the label).
			return this._formatLabel(
				this.options.editableTags
					? $( tag ).find( 'input' ).val()
					: $( tag ).find( '.tagadata-label' ).text()
			);
		},

		/**
		 * Returns whether the tag with an given label is present within the list of tags already
		 *
		 * @param string label
		 * @return Boolean
		 */
		hasTag: function( label ) {
			return this.getTag( label ) !== null;
		},

		_formatLabel: function( str ) {
			str = $.trim( str );
			if( this.options.caseSensitive ) {
				return str;
			}
			return str.toLowerCase();
		},

		/**
		 * Highlights a tag for a short time
		 *
		 * @param tag
		 */
		highlightTag: function( tag ) {
			// highlight tag visually so the user knows the tag is in the list already
			// switch to highlighted class...
			tag.switchClass( '', 'tagadata-choice-existing ui-state-highlight', 150, 'linear', function() {
				// ... and remove it again (also remove 'remove' class to avoid confusio
				tag.switchClass( 'tagadata-choice-existing ui-state-highlight remove', '', 750, 'linear' );
			} );
		},

		/**
		 * This will add a new tag to the list of tags. If the tag exists in the list already, the already existing tag
		 * will be returned.
		 *
		 * @param String value
		 * @param String|Array additionalClasses
		 * @return jQuery
		 */
		createTag: function( value, additionalClasses ) {
			if( $.isArray( additionalClasses ) ) {
				additionalClasses = additionalClasses.join( ' ' );
			}
			var self = this;
			var tag = this.getTag( value );

			// Automatically trims the value of leading and trailing whitespace.
			value = this._formatLabel( value );

			if( tag !== null ) {
				// tag in list already, don't add it twice
				if( value !== '' ) {
					// highlight the already existing tag, except if it is the new tag input
					this.highlightTag( tag );
				}

				return tag;
			}

			var label = $( '<span>', {
				'class': 'tagadata-label' + ( this.options.onTagClicked ? ' tagadata-label-clickable' : '' )
			} );


			var input = ( $( '<input>', {
				name: this.options.itemName + '[' + this.options.fieldName + '][]'
			} ) );

			// Create tag.
			tag = $( '<li>' )
			.addClass( 'tagadata-choice ui-widget-content ui-state-default ui-corner-all' )
			.addClass( additionalClasses )
			.append( label );

			// Button for removing the tag.
			var removeTagIcon = $( '<span></span>' )
				.addClass( 'ui-icon ui-icon-close' );

			var removeTag = $( '<a><span class="text-icon">\xd7</span></a>' )// \xd7 is an X
				.addClass( 'tagadata-close' )
				.append( removeTagIcon )
				.click( function( e ) {
					// Removes a tag when the little 'x' is clicked.
					self.removeTag( tag );
				} );

			tag.append( removeTag );

			if( this.options.editableTags ) {
				var previousLabel; // for determining whether label has changed
				var addPlaceholder = function() {
					if( self.options.placeholderText ) {
						input.attr( 'placeholder', self.options.placeholderText );
						if( input.inputAutoExpand ) {
							input.inputAutoExpand();
						}
					}
				};
				var removePlaceholder = function() {
					if( self.options.placeholderText ) {
						input.removeAttr( 'placeholder' );
						if( input.inputAutoExpand ) {
							input.inputAutoExpand();
						}
					}
				};

				if( value === '' ) {
					addPlaceholder();
				}

				// input is the actual visible content
				input.attr( {
					type: 'text',
					value: value,
					'class': 'tagadata-label-text'
				} )
				.blur( function( e ) {
					var tag = input.closest( '.tagadata-choice' );

					// remove tag if it is empty already:
					if( self._formatLabel( input.val() ) === ''
						&& self.getTags().length > 1
						&& ! tag.is( '.tagadata-choice:last' )
					) {
						self.removeTag( tag );
					}
				} )
				.keypress( function( event ) {
						// store value before key evaluated to make comparison afterwards
						previousLabel = self.getTagLabel( tag );
				} )
				.keyup( function( event ) {
					var tagLabel = self.getTagLabel( tag );

					if( $.inArray( event.which, self.options.triggerKeys ) > -1 ) {
						// Key for finishing tag input was hit (e.g. ENTER)

						event.preventDefault();
						var targetTag = self.getHelperTag();

						if( tagLabel === '' ) {
							// enter hit on an empty tag, remove it...

							if( targetTag[0] !== tag[0] ) { // ... except for the helper tag
								self.removeTag( tag );
								self.highlightTag( targetTag );
							}
						}
						targetTag.find( 'input' ).focus();
					}

					if( tagLabel !== previousLabel ) {
						// input has changed compared with before key pressed!

						// Handle non-unique tags (conflicts):
						var equalTags = self._getTags( previousLabel ).add( tag );
						( equalTags.length <= 2
							? equalTags // only two tags WERE equal, so the conflict is resolved for both
							: tag       // the other nodes still have the conflict, but this one doesn't
						).removeClass( 'tagadata-choice-equal' );

						equalTags = tagLabel !== ''
							? self._getTags( tagLabel )
							: $(); // don't highlight anything if empty (will be removed anyhow)

						if( equalTags.length > 1 ) {
							// mark as equal
							equalTags.addClass( 'tagadata-choice-equal' );
						}

						// if this is the tag before the helper and its value has just been emptied, remove it
						// and jump into the helper:
						if( tagLabel === '' && self.getHelperTag().prev( tag ).length ) {
							self.removeTag( tag );
							self.getHelperTag().find( 'input' ).focus();
							return;
						}

						// Check whether the tag is modified/new compared to initial state:
						if( $.inArray( tagLabel, self.originalTags ) < 0 ) {
							tag.addClass( 'tagadata-choice-modified' );
						} else {
							tag.removeClass( 'tagadata-choice-modified' );
						}

						// Additional checks in case this is the helper tag
						if( tag.is( '.tagadata-choice:last' ) ) {
							// Tag is completely emty now and the last one, consider it the helper tag:
							if( tagLabel === '' ) {
								addPlaceholder();
								tag.addClass( 'tagadata-choice-empty' );
							} else {
								removePlaceholder();
								tag.removeClass( 'tagadata-choice-empty' );
							}
						}

						// trigger once for widget, once for tag itself
						$( tag ).triggerHandler( 'tagadatatagchanged', previousLabel );
						self._trigger( 'tagChanged', tag, previousLabel );
					}
				} )
				.appendTo( label );

				// if auto expand is available, use it for tags!
				if( input.inputAutoExpand ) {
					input.inputAutoExpand( {
						maxWidth: function() {
							var origCssDisplay = self.tagList.css( 'display' );
							self.tagList.css( 'display', 'block' );
							var width = self.tagList.width();
							self.tagList.css( 'display', origCssDisplay );
							return width;
						}
					} );
				}
			} else {
				// we need input only for the form to contain the data
				input.attr( {
					type: 'hidden',
					style: 'display:none;'
				} )
				.appendTo( tag );

				label.text( value )
				.addClass( 'tagadata-label-text' );
			}

			/// / insert tag
			this.tagList.append( tag );

			if( value !== '' ) {
				// only trigger if this isn't the helper tag
				this._trigger( 'tagAdded', null, tag );
			}

			return tag;
		},

		/**
		 * Returns an empty tag at the end of the tag list. If none exists, this will create one and return it.
		 *
		 * @return jQuery
		 */
		getHelperTag: function() {
			var tag = this.tagList.find( '.tagadata-choice:last' );
			if( this.getTagLabel( tag ) !== '' ) {
				// no helper yet, create one!
				tag = this.createTag( '' );

				// make sure a new helper will be created when something is inserted into helper:
				var self = this;
				var helperManifestation = function( e ) {
					var tagLabel = self.getTagLabel( tag );
					if( tagLabel !== '' ) {
						self._trigger( 'tagAdded', null, tag );
						self.getHelperTag();
						tag.off( 'tagadatatagchanged', helperManifestation );
					}
				};
				tag.on( 'tagadatatagchanged', helperManifestation );
			}

			tag.appendTo( this.tagList );

			this.tagList.children().removeClass( 'tagadata-choice-empty' );
			tag.addClass( 'tagadata-choice-empty' );

			return tag;
		},

		/**
		 * Returns whether the given tag is the helper tag. Doesn NOT create a helper tag if it isn't.
		 *
		 * @param tag jQuery
		 * @return Boolean
		 */
		isHelperTag: function( tag ) {
			var helperTag = this.tagList.find( '.tagadata-choice:last' );
			return tag[0] === helperTag[0];
		},

		/**
		 * Removes a tag which can be received by getTag() via its label.
		 *
		 * @param jQuery tag
		 * @param animate (optional)
		 * @return Boolean
		 */
		removeTag: function( tag, animate ) {
			animate = animate || this.options.animate;

			tag = $( tag );

			this._trigger( 'beforeTagRemoved', null, tag );

			// make sure conflicts with tag which has same content will be marked as resolved:
			var equalTags = this._getTags( this.getTagLabel( tag ) );
			if( equalTags.length == 2 ) {
				equalTags.removeClass( 'tagadata-choice-equal' );
			}

			// Animate the removal.
			if( animate ) {
				tag.addClass( 'tagadata-choice-removed' );
				tag.fadeOut( 'fast' ).hide( 'blind', {direction: 'horizontal'}, 'fast',function() {
					tag.remove(); //TODO/FIXME: danwe: This won't work for some reason, callback not called, fadeOut not happening!
				} ).dequeue();
			} else {
				tag.remove();
			}

			this._trigger( 'tagRemoved', null, tag );
			return true;
		},

		removeAll: function() {
			// Removes all tags.
			var self = this;
			this.tagList.children( '.tagadata-choice' ).each( function( index, tag ) {
				self.removeTag( tag, false );
			} );
		},

		/**
		 * Destroys the element and only leaves the original ul element (including all new elements)
		 */
		destroy: function() {
			var self = this;

			this.tagList
			.removeClass( 'tagadata ui-widget ui-widget-content ui-corner-all' )
			.off( 'click.tagadata' );

			this.tagList.children( 'li' ).each( function() {
				var tag = $( this );
				var text = self.getTagLabel( tag );
				tag
				.removeClass( 'tagadata-choice tagadata-choice-removed ui-widget-content ui-state-default ui-corner-all ui-state-highlight remove' )
				.empty()
				.text( text ); // also removes all the helper stuff within
			} );

			return $.Widget.prototype.destroy.call( this );
		}

	} );

} )( jQuery );
