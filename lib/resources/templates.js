/**
 * Template engine's JavaScript implementation.
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, $, undefined ) {
	'use strict';

	/*
	 * Object constructor for templates.
	 * mediawiki.jQueryMsg replaces mw.Message's native simple parser method performing some
	 * replacements when certain characters are detected in the message string. Since such replacing
	 * could interfere with templates, the simple parser is re-implemented in the Template
	 * constructor.
	 *
	 * @since 0.4
	 *
	 * @constructor
	 */
	mw.Template = function() { mw.Message.apply( this, arguments ); };
	mw.Template.prototype = $.extend(
		{},
		mw.Message.prototype,
		{ constructor: mw.Template }
	);

	/**
	 * Returns the parsed plain template. (Overridden due to IE8 returning objects instead of
	 * strings from mw.Message's native plain() method.)
	 * @see mw.Message.plain
	 *
	 * @return {string}
	 */
	mw.Template.prototype.plain = function() {
		return this.parser();
	};

	/**
	 * @see mw.Message.parser
	 *
	 * @return {String}
	 */
	mw.Template.prototype.parser = function () {
		var parameters = this.parameters;
		return this.map.get( this.key ).replace( /\$(\d+)/g, function ( str, match ) {
			var index = parseInt( match, 10 ) - 1;
			return parameters[index] !== undefined ? parameters[index] : '$' + match;
		} );
	};

	/**
	 * Returns a template filled with the specified parameters, similar to wfTemplate().
	 * @see mw.message
	 *
	 * @since 0.2
	 *
	 * @param {String} key Key of the template to get.
	 * @param {String|jQuery|Array} parameter1 First argument in a list of variadic arguments, each
	 *        a parameter for $N replacement in templates. Instead of making use of variadic
	 *        arguments, an array may be passed as first parameter.
	 * @return {jQuery}
	 */
	mw.template = function( key, parameter1 /* [, parameter2] */ ) {
		var i,
			params = [],
			template, parsedTemplate,
			strippedTemplate, strippedParsedTemplate,
			$wrappedTemplate,
			tempParams = [],
			delayedParams = [],
			nodeName;

		if ( parameter1 !== undefined ) {
			if ( $.isArray( parameter1 ) ) {
				params = parameter1;
			} else { // support variadic arguments
				params = Array.prototype.slice.call( arguments );
				params.shift();
			}
		}

		// pre-parse the template inserting strings and placeholder nodes for jQuery objects
		// jQuery objects will be appended after the template has been parsed to not lose any
		// references
		for ( i = 0; i < params.length; i++ ) {
			if ( typeof params[i] === 'string' ) {
				// insert strings into the template directly but have them parsed by the browser
				// to detect HTML entities properly (e.g. a &nbsp; in Firefox would show up as a
				// space instead of an entity which would cause an invalid HTML error)
				tempParams.push( $( '<div/>' ).html( mw.html.escape( params[i] ) ).html() );
			} else if ( params[i] instanceof jQuery ) {
				// construct temporary placeholder nodes
				// (using an actual invalid class name to not interfere with any other node)
				nodeName = params[i][0].nodeName.toLowerCase();
				tempParams.push( '<' + nodeName + ' class="--mwTemplate"></' + nodeName + '>' );
				delayedParams.push( params[i] );
			} else {
				throw new Error( 'mw.template: Wrong parameter type. Pass either String or jQuery.' );
			}
		}

		template = new mw.Template( mw.templates.store, key, tempParams );

		// wrap template inside a html container to be able to easily access all temporary nodes and
		// insert any jQuery objects
		$wrappedTemplate = $( '<html/>' ).html( template.plain() );

		// The following lines check if the HTML to be created is actually valid. If not, an error
		// is thrown making the developer aware of the conflict rather than passing anything broken

		// html element automatically creates a body tag for certain elements that fit right into
		// the body - not for tags like <tr>
		parsedTemplate = $wrappedTemplate.html();
		if ( $wrappedTemplate.children( 'body').length ) {
			parsedTemplate = $wrappedTemplate.children( 'body').html();
		}

		/**
		 * Helper function to strip HTML tags that may be generated automatically like <tbody>
		 * as well as all node attributes.
		 *
		 * @param {String} string
		 * @return {String}
		 */
		function strip( string ) {
			var tagsToIgnore = [ 't(?:head|body|foot)' ],
				inTag = false,
				readTag = false,
				tag = '',
				outTag = false,
				filteredString = '',
				character = '',
				i;

			$.each( tagsToIgnore, function( i, tag ) {
				// ignore case since IE8 will convert tag names to upper case
				var re = new RegExp( '<\\/?' + tag + '[^>]*>', 'gi' );
				string = string.replace( re, '' );
			} );

			// strip white space between tags as well since it might cause interference
			string = string.replace( />\s+</g, '><' );

			// Strip all attributes since they are not necessary for validating the HTML and would
			// cause interference in Firefox which re-converts &lt; and &gt; back to < and > when
			// parsing by setting through $.html().
			// Additionally, rip off any XML notation since jQuery will parse to HTML.
			// Cannot easily rely on regular expressions here since there exist incompatibilities
			// between browsers regarding complex regular expression (especially referring to
			// back-references in IE8).
			// The following is an example for a regular expression that may be used to do the
			// replacing. However, it does not work in IE8 and may cause errors for certain DOM
			// structures in other browsers as well:
			// string = string.replace( /(<\S+)(?:[^<>"']+(?:(["'])[^\2]*\2)?)*?\/?(>)/g, '$1$3' );
			for ( i = 0; i < string.length; i++ ) {
				character = string[i];
				if ( !inTag && !readTag && character === '<' ) {
					tag = '';
					inTag = true;
					readTag = true;
					filteredString += character;
				} else if ( inTag && ( character === ' ' || character === '/' && string[i + 1] === '>' ) ) {
					readTag = false;
				} else if ( inTag && ( character === '\'' || character === '"' ) ) {
					// skip all characters within an attribute's value
					i++;
					while ( string[i] !== character ) {
						i++;
					}
				} else if ( inTag && character === '>' ) {
					inTag = false;
					readTag = false;
					outTag = true;
					filteredString += character;
				} else if ( outTag && /\s/.test( character ) ) {
					continue; // omit white space between tag and text (IE8)
				} else if (
					( !inTag || inTag && readTag ) &&
					// strip line breaks inserted by IE8 that are not stripped by the regular
					// expression before the for loop
					character.charCodeAt( 0 ) !== 10 && character.charCodeAt( 0 ) !== 13
				) {
					filteredString += character;
					if ( outTag ) {
						outTag = false;
					}
				}
			}

			// We are not interested in letter case and since IE8 is causing problems due to having
			// jQuery parse the template will convert the tag names to upper case, just convert the
			// whole string to lower case.
			string = filteredString.toLowerCase();

			return string;
		}
		strippedTemplate = strip( template.plain() );
		strippedParsedTemplate = strip( parsedTemplate );

		// nodes or text got lost while being parsed which indicates that the generated HTML would
		// be invalid
		if ( strippedTemplate !== strippedParsedTemplate ) {
			throw new Error( 'mw.template: Tried to generate invalid HTML for template "' + key + '"' );
		}

		// replace temporary nodes with actual jQuery nodes
		$wrappedTemplate.find( '.--mwTemplate' ).each( function( i ) {
			$( this ).replaceWith( delayedParams[i] );
		} );

		if ( $wrappedTemplate.children( 'body' ).length ) {
			return $wrappedTemplate.children( 'body' ).contents();
		} else {
			return $wrappedTemplate.contents();
		}
	};

	/**
	 * Fetches a template and fills it with specified parameters. The template has to have a single
	 * root DOM element. All of its child nodes will then be appended to the jQuery object's DOM
	 * nodes.
	 * @see mw.template
	 *
	 * @since 0.3
	 *
	 * @param {String} template
	 * @param {String|jQuery|Array} parameter1 First argument in a list of variadic arguments, each
	 *        a parameter for $N replacement in templates. Instead of making use of variadic
	 *        arguments, an array may be passed as first parameter.
	 * @return {jQuery}
	 */
	$.fn.applyTemplate = function( /* [template, parameter1, parameter2] */ ) {
		var $template = mw.template.apply( null, arguments );

		if( $template.length !== 1 ) {
			throw new Error( 'Can not apply a template with more or less than one root node.' );
		}

		// copy template's root node children and classes to given root
		this.addClass( $template.prop( 'class' ) );

		// copy dir attribute if set
		if ( $template.prop( 'dir' ) !== '' ) {
			this.prop( 'dir', $template.prop( 'dir' ) );
		}

		$template.children().appendTo( this.empty() );

		return this;
	};

}( mediaWiki, jQuery ) );
