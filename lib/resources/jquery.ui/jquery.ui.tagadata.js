/**
 * 'tag-a-data' jQuery widget
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
 * @licence MIT license
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {string} [inputName]
 *         Name of the "name" attribute assigned to the input elements.
 *         Default: 'entity'
 *
 * @option {boolean} [editableTags]
 *         Defines whether the tags can be altered at all times. If true, the tags contain input
 *         boxes, so it can be tabbed over them or clicked inside to alter the value.
 *         Default: true
 *
 * @option {boolean} [caseSensitive]
 *         If true, tags with the same text but different capitalization can be inserted.
 *         Default: true
 *
 * @option {string} [placeholderText]
 *         Text used as placeholder in the input field if no text has been typed yet.
 *         Default: ''
 *
 * @option {boolean} [anmiate]
 *         Whether  to animate tag removals or not.
 *         Default: true
 *
 * @option {number[]} [triggerKeys]
 *         Keys which - when pressed in the input area - will trigger the current input to be added
 *         as tag. $.ui.keyCode members can be used for convenience.
 *         Default: []
 *
 * @event tagclicked
 *        Triggered when a tag is clicked.
 *        - {jQuery.Event}
 *        - {jQuery} $tag

 * @event tagchanged
 *        Triggered when a tag's label is changed.
 *        - {jQuery.Event}
 *        - {jQuery} $tag
 *        - {string} Former label
 *
 * @event tagadded
 *        Triggered when a new tag featuring a value is added.
 *        - {jQuery.Event}
 *        - {jQuery} $tag
 *
 * @event taginserted
 *        Triggered when a new tag is added (event is triggered for empty tags as well).
 *        - {jQuery.Event}
 *        - {jQuery} $tag
 *
 * @event beforetagremoved
 *        Triggered before a particular tag is removed from the list of tags.
 *        - {jQuery.Event}
 *        - {jQuery} $tag
 *
 * @event tagremoved
 *        Triggered after a tag has been removed.
 *        - {jQuery.Event}
 *        - {jQuery} $tag
 *
 * @event helpertagadded
 *        Triggered when a helper tag is added.
 *        - {jQuery.Event}
 *        - {jQuery} $tag
 */
( function( $ ) {
	'use strict';

$.widget( 'ui.tagadata', {

	/**
	 * @see jQuery.Widget.options
	 */
	options: {
		itemName: 'entity',
		editableTags: true,
		caseSensitive: true,
		placeholderText: null,
		animate: true,
		triggerKeys: []
	},

	/**
	 * @type {jQuery}
	 */
	_$tagList: null,

	/**
	 * @type {string[]}
	 */
	_initialTagLabels: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;

		this._$tagList = this.element.find( 'ul, ol' ).addBack().last();
		this._initialTagLabels = [];

		this._$tagList
		.addClass( 'tagadata' )
		.addClass( 'ui-widget ui-widget-content ui-corner-all' )
		.on( 'click.tagadata', function( event ) {
			var target = $( event.target );
			if( target.hasClass( 'tagadata-label' ) ) {
				self._trigger( 'tagClicked', event, target.closest( '.tagadata-choice' ) );
			}
		} );

		// Add existing tags from the list, if any
		this._$tagList.children( 'li' ).each( function() {
			var newTagLabel = $( this ).text();
			if( self._formatLabel( newTagLabel ) !== '' ) { // don't initialize empty tags here
				var $newTag = self.createTag( newTagLabel, $( this ).attr( 'class' ) );
				self._initialTagLabels.push( self.getTagLabel( $newTag ) );
				$( this ).remove(); // remove empty tag
			}
		} );

		// Create an empty input tag at the end:
		this.getHelperTag();
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		var self = this;

		this._$tagList
		.removeClass( 'tagadata ui-widget ui-widget-content ui-corner-all tagadata-enabled '
			+ 'tagadata-disabled' )
		.off( '.tagadata' );

		this._$tagList.children( 'li' ).each( function() {
			var $tag = $( this ),
				text = self.getTagLabel( $tag );

			if ( text === '' ) {
				$tag.remove(); // completely remove helper
			} else {
				$tag
				.removeClass( 'tagadata-choice tagadata-choice-removed ui-widget-content '
					+ 'ui-state-default ui-corner-all ui-state-highlight remove' )
				.empty()
				.off( '.' + this.widgetName )
				.text( text );

				$tag.find( 'input' ).off( '.' + this.widgetName );
			}
		} );

		return $.Widget.prototype.destroy.call( this );
	},

	/**
	 * Returns the nodes of all tags currently assigned. To get the actual text, use
	 * getTagLabel() on them.
	 * Empty tags are not returned, getHelperTag() may be used to receive empty tags.
	 * If tags conflict (same tag exists twice) only one of the corresponding DOM nodes is
	 * returned.
	 *
	 * @return {jQuery}
	 */
	getTags: function() {
		var self = this,
			$tags = $(),
			usedLabels = [];

		this._$tagList.children( '.tagadata-choice' ).each( function() {
			var $tag = $( this );

			// Check if already removed but still assigned till animations end:
			if( !$tag.hasClass( 'tagadata-choice-removed' ) ) {
				var tagLabel = self.getTagLabel( $tag );

				if( tagLabel !== '' && $.inArray( tagLabel, usedLabels ) === -1 ) {
					$tags = $tags.add( this );
					usedLabels.push( tagLabel );
				}
			}
		} );
		return $tags;
	},

	/**
	 * Returns a tag's element by its label. If the tag is not in the list, null will be returned.
	 *
	 * @param {string} label
	 * @return {jQuery|null}
	 */
	getTag: function( label ) {
		var self = this,
			result = null;

		this._$tagList.children( '.tagadata-choice' ).each( function() {
			var $tag = $( this );
			if( self._formatLabel( label ) === self._formatLabel( self.getTagLabel( $tag ) ) ) {
				result = $tag;
				return false;
			}
		} );
		return result;
	},

	/**
	 * Helper function to return all tags currently having the same value.
	 *
	 * @param {string} label
	 * @return {jQuery}
	 */
	_getTags: function( label ) {
		var self = this;
		label = this._formatLabel( label );

		return this._$tagList.children( '.tagadata-choice' ).filter( function() {
			return self.getTagLabel( $( this ) ) === label;
		} );
	},

	/**
	 * Returns the label of a tag represented by a DOM node.
	 *
	 * @param {jQuery} $tag
	 * @return {string}
	 */
	getTagLabel: function( $tag ) {
		var $input = $tag.find( 'input[type=text]' );
		return this._formatLabel(
			$input.length ? $input.val() : $tag.find( '.tagadata-label' ).text()
		);
	},

	/**
	 * @param {string} string
	 * @return {string}
	 */
	_formatLabel: function( string ) {
		string = $.trim( string );
		if( this.options.caseSensitive ) {
			return string;
		}
		return string.toLowerCase();
	},

	/**
	 * Highlights a tag for a short time.
	 *
	 * @param {jQuery} $tag
	 */
	highlightTag: function( $tag ) {
		$tag.switchClass(
			'',
			'tagadata-choice-existing ui-state-highlight',
			150,
			'linear',
			function() {
				// also remove 'remove' class to avoid confusion
				$tag.switchClass(
					'tagadata-choice-existing ui-state-highlight remove',
					'',
					750,
					'linear'
				);
			}
		);
	},

	/**
	 * Adds a new tag to the list of tags. If the tag exists in the list already, the existing tag
	 * will be returned.
	 *
	 * @param {string} value
	 * @param {string} [additionalClasses]
	 * @param {boolean} [forceTextInput]
	 * @return {jQuery}
	 */
	createTag: function( value, additionalClasses, forceTextInput ) {
		value = this._formatLabel( value );

		var $tag = this.getTag( value );

		if( $tag ) {
			if( value !== '' ) {
				// highlight the already existing tag, except if it is the new tag input
				this.highlightTag( $tag );
			}
			return $tag;
		}

		var $label = $( '<span>' ).addClass( 'tagadata-label' ),
			$input = $( '<input />' ).attr( 'name', this.options.itemName + '[]' );

		$tag = this._createTagNode().addClass( additionalClasses || '' ).append( $label );

		if( this.options.editableTags || forceTextInput ) {
			$input.attr( {
				type: 'text',
				value: value,
				'class': 'tagadata-label-text'
			} )
			.appendTo( $label );

			this._initTagEvents( $tag );
		} else {
			// we need input only for the form to contain the data
			$input.attr( {
				type: 'hidden',
				style: 'display:none;'
			} )
			.appendTo( $tag );

			$label.text( value )
			.addClass( 'tagadata-label-text' );
		}

		this._$tagList.append( $tag );

		if( value !== '' ) {
			// only trigger if this isn't the helper tag
			this._trigger( 'tagAdded', null, $tag );
		}
		this._trigger( 'tagInserted', null, $tag ); // event fired for both, helper and normal tags

		return $tag;
	},

	/**
	 *  @return {jQuery}
	 */
	_createTagNode: function() {
		var self = this;

		var $tag = $( '<li>' )
			.addClass( 'tagadata-choice ui-widget-content ui-state-default ui-corner-all' );

		var $removeTag = $( '<a><span class="text-icon">\xd7</span></a>' )// \xd7 is an X
			.addClass( 'tagadata-close' )
			.append( $( '<span>' ).addClass( 'ui-icon ui-icon-close' ) )
			.click( function() {
				if( !self.option( 'disabled' ) ) {
					self.removeTag( $tag );
				}
			} )
			.appendTo( $tag );

		return $tag.append( $removeTag );
	},

	/**
	 * @param {jQuery} $tag
	 */
	_initTagEvents: function( $tag ) {
		var self = this,
			$input = $tag.find( 'input' );

		$input
		.on( 'focus.' + this.widgetName, function() {
			$tag.addClass( 'tagadata-choice-active' );
		} )
		.on( 'blur.' + this.widgetName, function() {
			// remove tag if it is empty already:
			if( self._formatLabel( $input.val() ) === ''
				&& self.getTags().length > 1
				&& !$tag.is( '.tagadata-choice:last' )
			) {
				self.removeTag( $tag );
			}
		} )
		.on( 'eachchange.' + this.widgetName, function( event, oldValue ) {
			// input change registered, check whether tag was really changed...
			var oldNormalValue = self._formatLabel( oldValue ),
				newNormalValue = self._formatLabel( $input.val() );

			if( oldNormalValue !== newNormalValue ) {
				// trigger once for widget, once for tag itself
				$tag.triggerHandler( self.widgetEventPrefix + 'tagchanged', oldNormalValue );
				self._trigger( 'tagChanged', null, [$tag, oldNormalValue] );
			}
		} )
		.on( 'keydown.' + this.widgetName, function( event ) {
			if( $.inArray( event.which, self.options.triggerKeys ) > -1 ) {
				// Key for finishing tag input was hit (e.g. ENTER)

				event.preventDefault();
				var $targetTag = self.getHelperTag();

				if( self.getTagLabel( $tag ) === '' ) {
					// Remove tag if hit ENTER on an empty tag, except for the helper tag.
					if( $targetTag.get( 0 ) !== $tag.get( 0 ) ) {
						self.removeTag( $tag );
						self.highlightTag( $targetTag );
					}
				}
				$targetTag.find( 'input' ).focus();
			}
		} );

		$tag
		.on( this.widgetEventPrefix + 'tagchanged.' + this.widgetName,
			function( event, oldValue ) {
				var tagLabel = self.getTagLabel( $tag );

				// Handle non-unique tags (conflicts):
				var equalTags = self._getTags( oldValue ).add( $tag );
				( equalTags.length <= 2
					? equalTags // only two tags WERE equal, so the conflict is resolved for both
					: $tag       // the other nodes still have the conflict, but this one doesn't
				).removeClass( 'tagadata-choice-equal' );

				equalTags = tagLabel !== ''
					? self._getTags( tagLabel )
					: $(); // don't highlight anything if empty (will be removed anyhow)

				if( equalTags.length > 1 ) {
					equalTags.addClass( 'tagadata-choice-equal' );
				}

				// if this is the tag before the helper and its value has just been emptied, remove
				// it and jump into the helper:
				if( tagLabel === '' && self.getHelperTag().prev( $tag ).length ) {
					self.removeTag( $tag );
					self.getHelperTag().find( 'input' ).focus();
					return;
				}

				// Check whether the tag is modified/new compared to initial state:
				if( $.inArray( tagLabel, self._initialTagLabels ) === -1 ) {
					$tag.addClass( 'tagadata-choice-modified' );
				} else {
					$tag.removeClass( 'tagadata-choice-modified' );
				}
			}
		);
	},

	/**
	 * Returns an empty tag at the end of the tag list. If none exists, a new one will be created.
	 *
	 * @return {jQuery}
	 */
	getHelperTag: function() {
		var $tag = this._$tagList.find( '.tagadata-choice:last' );

		if( !$tag.length || this.getTagLabel( $tag ) !== '' ) {
			$tag = this._createHelperTag();
		}

		$tag.appendTo( this._$tagList );

		this._$tagList.children().removeClass( 'tagadata-choice-empty' );
		$tag.addClass( 'tagadata-choice-empty' );

		this._trigger( 'helperTagAdded', null, $tag );

		return $tag;
	},

	/**
	 * @return {jQuery}
	 */
	_createHelperTag: function() {
		var $tag = this.createTag( '', '', true ),
			input = $tag.find( 'input' );

		// Add placeholder and auto-expand afterwards:
		if( this.options.placeholderText ) {
			input.attr( 'placeholder', this.options.placeholderText );
			if( input.inputautoexpand ) {
				input.inputautoexpand( {
					expandOnResize: false
				} );
			}
		}

		// Make sure a new helper will be created when something is inserted into helper:
		var self = this;

		var detectHelperInput = function() {
			if( self.getTagLabel( $tag ) !== '' ) {
				// Remove placeholder.
				// NOTE: Can't do this on blurring the input because when clicking a button, the
				// click might fail because of the input box resizing.
				if( self.options.placeholderText && input.val() !== '' ) {
					input.removeAttr( 'placeholder' );
					if( input.inputautoexpand ) {
						input.inputautoexpand( {
							expandOnResize: false
						} );
					}
				}
				$tag.removeClass( 'tagadata-choice-empty' );
				self._trigger( 'tagAdded', null, $tag );
				self.getHelperTag();
				$tag.off( self.widgetEventPrefix + 'tagchanged', detectHelperInput );
			}
		};

		$tag.on( this.widgetEventPrefix + 'tagchanged.' + this.widgetName, detectHelperInput );

		return $tag;
	},

	/**
	 * Removes a tag.
	 *
	 * @param {jQuery} $tag
	 * @param {boolean} [animate]
	 * @return {boolean}
	 */
	removeTag: function( $tag, animate ) {
		var self = this;

		animate = animate || this.options.animate;

		if(
			!$tag.hasClass( 'tagadata-choice' )
			|| !$.contains( this._$tagList.get( 0 ), $tag.get( 0 ) )
		) {
			return false;
		}

		this._trigger( 'beforeTagRemoved', null, $tag );

		// Resolve label conflicts:
		var equalTags = this._getTags( this.getTagLabel( $tag ) );
		if( equalTags.length === 2 ) {
			equalTags.removeClass( 'tagadata-choice-equal' );
		}

		if( animate ) {
			$tag.addClass( 'tagadata-choice-removed' );
			$tag.fadeOut( 'fast' ).hide( 'blind', { direction: 'horizontal' }, 'fast', function() {
				$tag.remove();
				self._trigger( 'tagRemoved', null, $tag );
			} );
		} else {
			$tag.remove();
			this._trigger( 'tagRemoved', null, $tag );
		}

		return true;
	},

	/**
	 * Removes all tags.
	 */
	removeAll: function() {
		var self = this;
		this._$tagList.children( '.tagadata-choice' ).each( function() {
			self.removeTag( $( this ), false );
		} );
	},

	/**
	 * Returns whether two tags conflict by containing the same text.
	 *
	 * @return {boolean}
	 */
	hasConflict: function() {
		var self = this,
			hasConflict = false;

		this.getTags().each( function() {
			var $tag = $( this ),
				label = self.getTagLabel( $tag );

			if( self._getTags( label ).length > 1 ) {
				hasConflict = true;
				return false;
			}
		} );

		return hasConflict;
	},

	/**
	 * @see jQuery.Widget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'disabled' ) {
			var $input = this._$tagList.find( 'input' );

			if( value ) {
				this.getHelperTag().remove();
				$input.blur();
			} else {
				// Create helper tag if it does not exist already:
				this.getHelperTag();
			}

			$input.prop( 'disabled', value );
			this._$tagList[value ? 'addClass' : 'removeClass']( 'tagadata-disabled' );
		}

		return $.Widget.prototype._setOption.apply( this, arguments );
	}
} );

} )( jQuery );
