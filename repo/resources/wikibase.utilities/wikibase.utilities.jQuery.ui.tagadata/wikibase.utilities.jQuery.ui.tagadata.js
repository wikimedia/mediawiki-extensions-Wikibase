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
 * @file
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

			// Add existing tags from the list, if any.
			this.tagList.children( 'li' ).each( function() {
				var newTag = self.createTag( $( this ).html(), $( this ).attr( 'class' ) );
				self.originalTags.push( self.tagLabel( newTag ) );
				$( this ).remove();
			} );

			this.getHelperTag().focus(); // creates an empty input tag at the end.
		},

		_lastTag: function() {
			return this.tagList.children( '.tagadata-choice:last' );
		},

		/**
		 * Returns the labels of all tags currently assigned.
		 *
		 * @return String[]
		 */
		assignedTags: function() {
			// Returns an array of tag string values
			var self = this;
			var tags = [];

			this.tagList.children( '.tagadata-choice' ).each( function() {
				// check if already removed but still assigned till animations end. if so, don't add tag!
				if( !$( this ).hasClass( 'tagadata-choice-removed' ) ) {
					var label = self.tagLabel( this );
					if( label !== '' ) {
						tags.push( label );
					}
				}
			} );

			return tags;
		},

		_subtractArray: function( a1, a2 ) {
			var result = [];
			for( var i = 0; i < a1.length; i++ ) {
				if( $.inArray( a1[i], a2 ) === -1 ) {
					result.push( a1[i] );
				}
			}
			return result;
		},

		/**
		 * Returns the label of a tag represented by a DOM node.
		 *
		 * @param jQuery tag
		 * @return string
		 */
		tagLabel: function( tag ) {
			// Returns the tag's string label (input can be direct child or inside the label).
			return this._formatLabel(
				this.options.editableTags
					? $( tag ).find( 'input' ).val()
					: $( tag ).find( '.tagadata-label' ).text()
			);
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
				if( self._formatLabel( label ) === self._formatLabel( self.tagLabel( this ) ) ) {
					result = $( this );
					return false;
				}
			} );
			return result;
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
				var tagMerge = function( tag ) {
					// find out whether given tag has equivalent and remove it in that case.
					var tagLabel = self.tagLabel( tag );
					var equalTags = self.tagList.find('.tagadata-label input').filter( function() {
						return $( this ).val() === tagLabel;
					} );
					if( equalTags.length > 1 ) {
						// remove given tag
						equalTags = equalTags.not( tag.find('.tagadata-label input') );
						// remove given tag and highlight the other one
						if( tagLabel !== '' ) {
							self.highlightTag( $( equalTags[0] ).closest( '.tagadata-choice' ) );
						}
						self.removeTag( tag );
						return false;
					}
					return true;
				};

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
					var tagLabel = self.tagLabel( tag );

					if( ! tagMerge( tag ) ) {
						return; // tag has equivalent, merged them
					}

					// remove tag if it is empty already
					// tagMerge() doesn't cach this case, where we left a tag empty and there is no tag helper currently!
					if( self._formatLabel( input.val() ) === ''
						&& self.assignedTags().length > 1
						&& ! tag.is( '.tagadata-choice:last' )
					) {
						self.removeTag( tag );
					}
				} )
				.keypress( function( event ) {
						previousLabel = self.tagLabel( tag );
				} )
				.keyup( function( event ) {
					var tagLabel = self.tagLabel( tag );
					// check whether key for insertion was triggered
					if( $.inArray( event.which, self.options.triggerKeys ) > -1 ) {
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
						// Check whether the tag is modified/new compared to initial state:
						if( $.inArray( tagLabel, self.originalTags ) < 0 ) {
							tag.addClass( 'tagadata-choice-modified' );
						} else {
							tag.removeClass( 'tagadata-choice-modified' );
						}

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

						self._trigger( 'tagChanged', tag, previousLabel );
					}
				} )
				.appendTo( label );
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

			this._trigger( 'tagAdded', null, tag );

			if( input.inputAutoExpand ) { // if auto expand is available, use it for tags!
				input.inputAutoExpand();
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
			if( this.tagLabel( tag ) !== '' ) {
				tag = this.createTag( '' );
			}
			tag.appendTo( this.tagList ); // make sure helper tag is appended at the end (not the case if '' already exists somewhere else)

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
			var helperTab = this.tagList.find( '.tagadata-choice:last' );
			return tag[0] === helperTab;
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
				var text = self.tagLabel( tag );
				tag
				.removeClass( 'tagadata-choice tagadata-choice-removed ui-widget-content ui-state-default ui-corner-all ui-state-highlight remove' )
				.empty()
				.text( text ); // also removes all the helper stuff within
			} );

			return $.Widget.prototype.destroy.call( this );
		}

	} );

} )( jQuery );
