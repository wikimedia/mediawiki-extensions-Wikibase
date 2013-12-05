<?php

namespace Wikibase;

use Html;
use ParserOptions;
use ParserOutput;
use Language;
use IContextSource;
use OutputPage;
use MWException;
use FormatJson;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Base class for creating views for all different kinds of Wikibase\Entity.
 * For the Wikibase\Entity this basically is what the Parser is for WikitextContent.
 *
 * @todo  We might want to re-design this at a later point, designing this as a more generic and encapsulated rendering
 *        of DataValue instances instead of having functions here for generating different parts of the HTML. Right now
 *        these functions require an EntityRevision while a DataValue (if it were implemented) should be sufficient.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 */
abstract class EntityView extends \ContextSource {

	/**
	 * @since 0.4
	 *
	 * @var SnakFormatter
	 */
	protected $snakFormatter;

	/**
	 * @var EntityInfoBuilder
	 */
	protected $entityRevisionLookup;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	protected $languageFallbackChain;

	/**
	 * Maps entity types to the corresponding entity view.
	 * FIXME: remove this stuff, big OCP violation
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	public static $typeMap = array(
		Item::ENTITY_TYPE => '\Wikibase\ItemView',
		Property::ENTITY_TYPE => '\Wikibase\PropertyView',

		// TODO: Query::ENTITY_TYPE
		'query' => '\Wikibase\QueryView',
	);

	/**
	 * @var string $messages in-process caching of i18n messages
	 */
	protected $messages = array();

	/**
	 * @since    0.1
	 *
	 * @param IContextSource|null $context
	 * @param SnakFormatter $snakFormatter
	 * @param Lib\PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		LanguageFallbackChain $languageFallbackChain
	) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
				&& $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_WIDGET ) {
			throw new \InvalidArgumentException( '$snakFormatter is expected to return text/html, not '
					. $snakFormatter->getFormat() );
		}

		$this->setContext( $context );
		$this->snakFormatter = $snakFormatter;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityInfoBuilder = $entityInfoBuilder;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->idParser = $idParser;
		$this->languageFallbackChain = $languageFallbackChain;
	}

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to render
	 * @param \Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtml( EntityRevision $entityRevision, Language $lang = null, $editable = true ) {
		wfProfileIn( __METHOD__ );

		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.
		if ( !$lang ) {
			$lang = $this->getLanguage();
		}

		$entityId = $entityRevision->getEntity()->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes
		$html = '';

		$html .= wfTemplate( 'wb-entity',
			$entityRevision->getEntity()->getType(),
			$entityId,
			$lang->getCode(),
			$lang->getDir(),
			$this->getInnerHtml( $entityRevision, $lang, $editable )
		);

		// show loading spinner as long as JavaScript is initialising;
		// the fastest way to show the loading spinner is placing the script right after the
		// corresponsing html
		$html .= Html::inlineScript( '
			$( ".wb-entity" ).fadeTo( 0, .3 ).after( function() {
				var $div = $( "<div/>" ).addClass( "wb-entity-spinner mw-small-spinner" );
				$div.css( "top", $div.height() + "px" );
				$div.css(
					( "' . $lang->getDir() . '" === "rtl" ) ? "right" : "left",
					( parseInt( $( this ).width() / 2 ) - $div.width() / 2 ) + "px"
				);
				return $div;
			} );

			// Remove loading spinner after a couple of seconds in any case. (e.g. some resource
			// might not have been loaded silently, so JavaScript is not initialising)
			// Additionally attaching to window.error would only make sense before any other
			// JavaScript is parsed. Since the JavaScript is loaded in the header, it does not make
			// any sense to attach to window.error here.
			window.setTimeout( function() {
				$( ".wb-entity" ).fadeTo( 0, 1 );
				$( ".wb-entity-spinner" ).remove();
			}, 7000 );
		' );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	protected function getFormattedIdForEntity( Entity $entity ) {
		if ( !$entity->getId() ) {
			return ''; //XXX: should probably throw an exception
		}

		return $entity->getId()->getPrefixedId();
	}

	/**
	 * Builds and returns the inner HTML for representing a whole WikibaseEntity. The difference to getHtml() is that
	 * this does not group all the HTMl within one parent node as one entity.
	 *
	 * @string
	 *
	 * @param EntityRevision $entityRevision
	 * @param \Language $lang
	 * @param bool $editable
	 * @return string
	 */
	public function getInnerHtml( EntityRevision $entityRevision, Language $lang, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$claims = '';
		$languageTerms = '';

		if ( $entityRevision->getEntity()->getType() === 'item' ) {
			$claims = $this->getHtmlForClaims( $entityRevision->getEntity(), $lang, $editable );
		}

		$languageTerms = $this->getHtmlForLanguageTerms( $entityRevision->getEntity(), $lang, $editable );

		$html = wfTemplate( 'wb-entity-content',
			$this->getHtmlForLabel( $entityRevision->getEntity(), $lang, $editable ),
			$this->getHtmlForDescription( $entityRevision->getEntity(), $lang, $editable ),
			$this->getHtmlForAliases( $entityRevision->getEntity(), $lang, $editable ),
			$this->getHtmlForToc( $lang ),
			$languageTerms,
			$claims
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the html for the toc.
	 *
	 * @param Language|null $lang
	 * @return string
	 */
	protected function getHtmlForToc( Language $lang = null ) {
		$tocContent = '';
		$tocSections = $this->getTocSections( $lang );

		if( empty( $tocSections ) ) {
			return '';
		}

		$i = 1;

		foreach( $tocSections as $id => $message ) {
			$tocContent .= wfTemplate( 'wb-entity-toc-section',
				$i++,
				$id,
				wfMessage( $message )->text()
			);
		}

		$toc = wfTemplate( 'wb-entity-toc',
			wfMessage( 'toc' )->text(),
			$tocContent
		);

		return $toc;
	}

	/**
	 * Returns the sections that should displayed in the toc.
	 *
	 * @param Language|null $lang
	 * @return array( link target => system message key )
	 */
	protected function getTocSections( Language $lang = null ) {
		if( !is_null( $lang ) && count( $this->getExtraUserLanguages( $lang, $this->getUser() ) ) > 0 ) {
			return array( 'wb-terms' => 'wikibase-terms' );
		} else {
			return array();
		}
	}

	protected function makeParserOptions( ) {
		$options = ParserOptions::newFromContext( $this );
		$options->setEditSection( false ); //NOTE: editing is disabled per default
		return $options;
	}

	/**
	 * Renders an entity into an ParserOutput object
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision      $entityRevision the entity to analyze/render
	 * @param null|\ParserOptions $options parser options. If nto provided, the local context will be used to create generic parser options.
	 * @param bool                $generateHtml whether to generate HTML. Set to false if only interested in meta-info. default: true.
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityRevision $entityRevision, ParserOptions $options = null, $generateHtml = true ) {
		wfProfileIn( __METHOD__ );

		if ( !$options ) {
			$options = $this->makeParserOptions();
		}

		$langCode = $options->getTargetLanguage();
		$editable = $options->getEditSection(); //XXX: apparently, EditSections isn't included in the parser cache key?!

		//@todo: would be nice to disable editing if the user isn't allowed to do that.
		//@todo: but this breaks the parser cache! So this needs to be done from the outside, per request.
		//if ( !$this->getTitle()->quickUserCan( "edit" ) ) {
		//	$editable = false;
		//}

		// fresh parser output with entity markup
		$pout = new ParserOutput();

		$allSnaks = $entityRevision->getEntity()->getAllSnaks();

		// treat referenced entities as page links ------
		$refFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $refFinder->findSnakLinks( $allSnaks );

		foreach ( $usedEntityIds as $entityId ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		// treat URL values as external links ------
		$urlFinder = new ReferencedUrlFinder( $this->dataTypeLookup );
		$usedUrls = $urlFinder->findSnakLinks( $allSnaks );

		foreach ( $usedUrls as $url ) {
			$pout->addExternalLink( $url );
		}

		if ( $generateHtml ) {
			$html = $this->getHtml( $entityRevision, $langCode, $editable );
			$pout->setText( $html );
		}

		//@todo: record sitelinks as iwlinks
		//@todo: record CommonsMedia values as imagelinks

		// make css available for JavaScript-less browsers
		$pout->addModuleStyles( array(
			'wikibase.common',
			'wikibase.toc',
			'jquery.ui.core',
			'jquery.wikibase.statementview',
			'jquery.wikibase.toolbar',
		) );

		// make sure required client sided resources will be loaded:
		$pout->addModules( 'wikibase.ui.entityViewInit' );

		//FIXME: some places, like Special:NewItem, don't want to override the page title.
		//       But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//       So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		wfProfileOut( __METHOD__ );
		return $pout;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's label.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity the entity to render
	 * @param \Language $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForLabel( Entity $entity, Language $lang, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$label = $entity->getLabel( $lang->getCode() );
		$editUrl = $this->getEditUrl( 'SetLabel', $entity, $lang );
		$prefixedId = $this->getFormattedIdForEntity( $entity );

		$html = wfTemplate( 'wb-label',
			$prefixedId,
			wfTemplate( 'wb-property',
				$label === false ? 'wb-value-empty' : '',
				htmlspecialchars( $label === false ? wfMessage( 'wikibase-label-empty' )->text() : $label ),
				wfTemplate( 'wb-property-value-supplement', wfMessage( 'parentheses', $prefixedId ) )
					. $this->getHtmlForEditSection( $editUrl )
			)
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's description.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity the entity to render
	 * @param \Language $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForDescription( Entity $entity, Language $lang, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$description = $entity->getDescription( $lang->getCode() );
		$editUrl = $this->getEditUrl( 'SetDescription', $entity, $lang );

		$html = wfTemplate( 'wb-description',
			wfTemplate( 'wb-property',
				$description === false ? 'wb-value-empty' : '',
				htmlspecialchars( $description === false ? wfMessage( 'wikibase-description-empty' )->text() : $description ),
				$this->getHtmlForEditSection( $editUrl )
			)
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's aliases.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity the entity to render
	 * @param \Language $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForAliases( Entity $entity, Language $lang, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$aliases = $entity->getAliases( $lang->getCode() );
		$editUrl = $this->getEditUrl( 'SetAliases', $entity, $lang );

		if ( empty( $aliases ) ) {
			$html = wfTemplate( 'wb-aliases-wrapper',
				'wb-aliases-empty',
				'wb-value-empty',
				wfMessage( 'wikibase-aliases-empty' )->text(),
				$this->getHtmlForEditSection( $editUrl, 'span', 'add' )
			);
		} else {
			$aliasesHtml = '';
			foreach( $aliases as $alias ) {
				$aliasesHtml .= wfTemplate( 'wb-alias', htmlspecialchars( $alias ) );
			}
			$aliasList = wfTemplate( 'wb-aliases', $aliasesHtml );

			$html = wfTemplate( 'wb-aliases-wrapper',
				'',
				'',
				wfMessage( 'wikibase-aliases-label' )->text(),
				$aliasList . $this->getHtmlForEditSection( $editUrl )
			);
		}

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Selects the languages for the terms to display on first try, based on the current user and
	 * the available languages.
	 *
	 * @since 0.4
	 *
	 * @param Language $lang
	 * @param User $user
	 * @return string[] Selected language codes
	 */
	private function getExtraUserLanguages( Language $lang , User $user ) {
		wfProfileIn( __METHOD__ );
		$result = array();

		// if the Babel extension is installed, add all languages of the user
		if ( class_exists( 'Babel' ) && ( ! $user->isAnon() ) ) {
			$result = \Babel::getUserLanguages( $user );
			if( $lang !== null ) {
				$result = array_diff( $result, array( $lang->getCode() ) );
			}
		}
		wfProfileOut( __METHOD__ );
		return $result;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's collection of terms.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity the entity to render
	 * @param \Language $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForLanguageTerms( Entity $entity, \Language $lang, $editable = true ) {
		$languages = $this->getExtraUserLanguages( $lang, $this->getUser() );
		if ( count ( $languages ) === 0 ) {
			return '';
		}

		wfProfileIn( __METHOD__ );

		$html = $thead = $tbody = '';

		$labels = $entity->getLabels();
		$descriptions = $entity->getDescriptions();

		$html .= wfTemplate( 'wb-terms-heading', wfMessage( 'wikibase-terms' ) );

		$specialLabelPage = \SpecialPageFactory::getPage( "SetLabel" );
		$specialDescriptionPage = \SpecialPageFactory::getPage( "SetDescription" );
		$rowNumber = 0;
		foreach( $languages as $language ) {

			$label = array_key_exists( $language, $labels ) ? $labels[$language] : false;
			$description = array_key_exists( $language, $descriptions ) ? $descriptions[$language] : false;

			$alternatingClass = ( $rowNumber++ % 2 ) ? 'even' : 'uneven';

			$editLabelLink = $specialLabelPage->getTitle()->getLocalURL()
				. '/' . $this->getFormattedIdForEntity( $entity ) . '/' . $language;

			// TODO: this if is here just until the SetDescription special page exists and
			// can be removed then
			if ( $specialDescriptionPage !== null ) {
				$editDescriptionLink = $specialDescriptionPage->getTitle()->getLocalURL()
					. '/' . $this->getFormattedIdForEntity( $entity ) . '/' . $language;
			} else {
				$editDescriptionLink = '';
			}

			$tbody .= wfTemplate( 'wb-term',
				$language,
				$alternatingClass,
				htmlspecialchars( Utils::fetchLanguageName( $language ) ),
				htmlspecialchars( $label !== false ? $label : wfMessage( 'wikibase-label-empty' ) ),
				htmlspecialchars( $description !== false ? $description : wfMessage( 'wikibase-description-empty' ) ),
				$this->getHtmlForEditSection( $editLabelLink ),
				$this->getHtmlForEditSection( $editDescriptionLink ),
				$label !== false ? '' : 'wb-value-empty',
				$description !== false ? '' : 'wb-value-empty',
				$this->getTitle()->getLocalURL() . '?setlang=' . $language
			);
		}

		$html = $html . wfTemplate( 'wb-terms-table', $tbody );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's claims.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity the entity to render
	 * @param \Language $lang the language to use for rendering. if not given, the local
	 *        context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForClaims( Entity $entity, Language $lang, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$languageCode = $lang->getCode();

		$claims = $entity->getClaims();
		$html = '';

		$html .= wfTemplate(
			'wb-section-heading',
			wfMessage( 'wikibase-statements' ),
			'claims' // ID - TODO: should not be added if output page is not the entity's page
		);

		// aggregate claims by properties
		$claimsByProperty = array();
		foreach( $claims as $claim ) {
			$propertyId = $claim->getMainSnak()->getPropertyId();
			$claimsByProperty[$propertyId->getNumericId()][] = $claim;
		}

		$labels = $this->getPropertyLabels( $entity, $languageCode );

		/**
		 * @var string $claimsHtml
		 * @var Claim[] $claims
		 */
		$claimsHtml = '';
		foreach( $claimsByProperty as $claims ) {
			$propertyHtml = '';

			$propertyId = $claims[0]->getMainSnak()->getPropertyId();
			$propertyKey = $propertyId->getSerialization();
			$propertyLabel = isset( $labels[$propertyKey] ) ? $labels[$propertyKey] : $propertyKey;
			$propertyLink = \Linker::link(
				$this->entityTitleLookup->getTitleForId( $propertyId ),
				htmlspecialchars( $propertyLabel )
			);

			$htmlForEditSection = $this->getHtmlForEditSection( '', 'span' ); // TODO: add link to SpecialPage

			$claimHtmlGenerator = new ClaimHtmlGenerator(
				$this->snakFormatter
			);

			foreach( $claims as $claim ) {
				$propertyHtml .= $claimHtmlGenerator->getHtmlForClaim( $claim, $htmlForEditSection );
			}

			$toolbarHtml = wfTemplate( 'wikibase-toolbar',
				'wb-addtoolbar',
				// TODO: add link to SpecialPage
				$this->getHtmlForEditSection( '', 'span', 'add' )
			);

			$claimsHtml .= wfTemplate( 'wb-claimlistview',
				$propertyHtml,
				wfTemplate( 'wb-claimgrouplistview-groupname', $propertyLink ) . $toolbarHtml
			);

		}

		$claimgrouplistviewHtml = wfTemplate( 'wb-claimgrouplistview', $claimsHtml, '' );

		// TODO: Add link to SpecialPage that allows adding a new claim.
		$html = $html . wfTemplate( 'wb-claimlistview', $claimgrouplistviewHtml, '' );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Returns a toolbar with an edit link for a single statement. Equivalent to edit toolbar in JavaScript but with
	 * an edit link pointing to a special page where the statement can be edited. In case JavaScript is available, this
	 * toolbar will be removed an replaced with the interactive JavaScript one.
	 *
	 * @since 0.2
	 *
	 * @param string $url specifies the URL for the button, default is an empty string
	 * @param string $tag allows to specify the type of the outer node
	 * @param string $action by default 'edit', for aliases this could also be 'add'
	 * @param bool $enabled can be set to false to display the button disabled
	 *
	 * @return string
	 */
	public function getHtmlForEditSection( $url = '', $tag = 'span', $action = 'edit', $enabled = true ) {
		wfProfileIn( __METHOD__ );

		$key = $action === 'add' ? 'wikibase-add' : 'wikibase-edit';
		$buttonLabel = $this->getContext()->msg( $key )->text();

		$button = ( $enabled ) ?
			wfTemplate( 'wikibase-toolbarbutton',
				$buttonLabel,
				$url // todo: add link to special page for non-JS editing
			) :
			wfTemplate( 'wikibase-toolbarbutton-disabled',
				$buttonLabel
			);

		$html = wfTemplate( 'wb-editsection',
			$tag,
			wfTemplate( 'wikibase-toolbar',
				'',
				wfTemplate( 'wikibase-toolbareditgroup',
					'',
					wfTemplate( 'wikibase-toolbar', '', $button )
				)
			)
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Returns the url of the editlink.
	 *
	 * @since    0.4
	 *
	 * @param string  $specialpagename
	 * @param Entity  $entity
	 * @param \Language $lang|null
	 *
	 * @return string
	 */
	protected function getEditUrl( $specialpagename, Entity $entity, Language $lang = null ) {
		$specialpage = \SpecialPageFactory::getPage( $specialpagename );

		if ( $specialpage === null ) {
			return ''; //XXX: this should throw an exception?!
		}

		if ( $entity->getId() ) {
			$id = $this->getFormattedIdForEntity( $entity );
		} else {
			$id = ''; // can't skip this, that would confuse the order of parameters!
		}

		return $specialpage->getTitle()->getLocalURL()
				. '/' . wfUrlencode( $id )
				. ( $lang === null ? '' : '/' . wfUrlencode( $lang->getCode() ) );
	}

	/**
	 * Outputs the given entity to the OutputPage.
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision       $entityRevision the entity to output
	 * @param null|\OutputPage    $out the output page to write to. If not given, the local context will be used.
	 * @param null|\ParserOptions $options parser options to use for rendering. If not given, the local context will be used.
	 * @param null|\ParserOutput  $pout optional parser object - provide this if you already have a parser options for
	 *                            this entity, to avoid redundant rendering.
	 * @return \ParserOutput the parser output, for further processing.
	 *
	 * @todo: fixme: currently, only one entity can be shown per page, because the entity's id is in a global JS config variable.
	 */
	public function render( EntityRevision $entityRevision, OutputPage $out = null, ParserOptions $options = null, ParserOutput $pout = null ) {
		wfProfileIn( __METHOD__ );

		$isPoutSet = $pout !== null;

		if ( !$out ) {
			$out = $this->getOutput();
		}

		if ( !$pout ) {
			if ( !$options ) {
				$options = $this->makeParserOptions();
			}

			$pout = $this->getParserOutput( $entityRevision, $options, true );
		}

		$langCode = null;
		if ( $options ) {
			//XXX: This is deprecated, and in addition it will quite often fail so we need a fallback.
			$langCode = $options->getTargetLanguage();
		}
		if ( !$isPoutSet && is_null( $langCode ) ) {
			//XXX: This is quite ugly, we don't know that this language is the language that was used to generate the parser output object.
			$langCode = $this->getLanguage()->getCode();
		}

		// overwrite page title
		$out->setPageTitle( $pout->getTitleText() );

		// register JS stuff
		$editableView = $options->getEditSection(); //XXX: apparently, EditSections isn't included in the parser cache key?!
		$this->registerJsConfigVars( $out, $entityRevision, $langCode, $editableView ); //XXX: $editableView should *not* reflect user permissions

		$out->addParserOutput( $pout );
		wfProfileOut( __METHOD__ );
		return $pout;
	}

	/**
	 * Helper function for registering any JavaScript stuff needed to show the entity.
	 * @todo Would be much nicer if we could do that via the ResourceLoader Module or via some hook.
	 *
	 * @since 0.1
	 *
	 * @param OutputPage    $out the OutputPage to add to
	 * @param EntityRevision  $entityRevision the entity for which we want to add the JS config
	 * @param string         $langCode the language used for showing the entity.
	 * @param bool           $editableView whether entities on this page should be editable.
	 *                       This is independent of user permissions.
	 *
	 * @todo: fixme: currently, only one entity can be shown per page, because the entity's id is in a global JS config variable.
	 */
	public function registerJsConfigVars( OutputPage $out, EntityRevision $entityRevision, $langCode, $editableView = false  ) {
		wfProfileIn( __METHOD__ );

		$user = $this->getUser();
		$entity = $entityRevision->getEntity();
		$title = $this->entityTitleLookup->getTitleForId( $entityRevision->getEntity()->getId() );

		//TODO: replace wbUserIsBlocked this with more useful info (which groups would be required to edit? compare wgRestrictionEdit and wgRestrictionCreate)
		$out->addJsConfigVars( 'wbUserIsBlocked', $user->isBlockedFrom( $title ) ); //NOTE: deprecated

		// tell JS whether the user can edit
		$out->addJsConfigVars( 'wbUserCanEdit', $title->userCan( 'edit', $user, false ) ); //TODO: make this a per-entity info
		$out->addJsConfigVars( 'wbIsEditView', $editableView );  //NOTE: page-wide property, independent of user permissions

		$out->addJsConfigVars( 'wbEntityType', $entity->getType() );
		$out->addJsConfigVars( 'wbDataLangName', Utils::fetchLanguageName( $langCode ) );

		// entity specific data
		$out->addJsConfigVars( 'wbEntityId', $this->getFormattedIdForEntity( $entity ) );

		// copyright warning message
		$out->addJsConfigVars( 'wbCopyright', array(
			'version' => Utils::getCopyrightMessageVersion(),
			'messageHtml' => Utils::getCopyrightMessage()->parse(),
		) );

		$experimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$out->addJsConfigVars( 'wbExperimentalFeatures', $experimental );

		// TODO: use injected id formatter
		$serializationOptions = new SerializationOptions();
		$serializationOptions->setLanguages( Utils::getLanguageCodes() + array( $langCode => $this->languageFallbackChain ) );

		$serializerFactory = new SerializerFactory( $serializationOptions );
		$serializer = $serializerFactory->newSerializerForObject( $entity, $serializationOptions );

		$out->addJsConfigVars(
			'wbEntity',
			FormatJson::encode( $serializer->getSerialized( $entity ) )
		);

		// make information about other entities used in this entity available in JavaScript view:
		$refFinder = new ReferencedEntitiesFinder();

		$usedEntityIds = $refFinder->findSnakLinks( $entity->getAllSnaks() );
		$basicEntityInfo = $this->getBasicEntityInfo( $usedEntityIds, $langCode );

		$out->addJsConfigVars(
			'wbUsedEntities',
			FormatJson::encode( $basicEntityInfo )
		);

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Fetches some basic entity information required for the entity view in JavaScript from a
	 * set of entity IDs.
	 * @since 0.4
	 *
	 * @param EntityId[] $entityIds
	 * @param string $langCode For the entity labels which will be included in one language only.
	 * @return array
	 */
	protected function getBasicEntityInfo( array $entityIds, $langCode ) {
		wfProfileIn( __METHOD__ );

		//TODO: apply language fallback! Restore fallback test case in EntityViewTest::provideRegisterJsConfigVars()
		$entities = $this->entityInfoBuilder->buildEntityInfo( $entityIds );
		$this->entityInfoBuilder->removeMissing( $entities );
		$this->entityInfoBuilder->addTerms( $entities, array( 'label', 'description' ), array( $langCode ) );
		$this->entityInfoBuilder->addDataTypes( $entities );
		$revisions = $this->attachRevisionInfo( $entities );

		wfProfileOut( __METHOD__ );
		return $revisions;
	}

	/**
	 * Fetches labels for all properties used as properties in snaks in the given entity.
	 *
	 * @param Entity $entity
	 * @param string $langCode the language code of the labels to fetch.
	 *
	 * @todo: we may also want to have the descriptions, in addition to the labels
	 * @return array maps property IDs to labels.
	 */
	protected function getPropertyLabels( Entity $entity, $langCode ) {
		wfProfileIn( __METHOD__ );
		//TODO: share cache with PropertyLabelResolver
		//TODO: ...or share info with getBasicEntityInfo

		//TODO: make a finder just for properties, so we don't have to filter
		$refFinder = new ReferencedEntitiesFinder();
		$entityIds = $refFinder->findSnakLinks( $entity->getAllSnaks() );
		$propertyIds = array_filter( $entityIds, function ( EntityId $id ) {
			return $id->getEntityType() === Property::ENTITY_TYPE;
		} );

		//NOTE: this is a bit hackish,it would be more appropriate to use a TermTable here.
		$entities = $this->entityInfoBuilder->buildEntityInfo( $propertyIds );
		$this->entityInfoBuilder->addTerms( $entities, array( 'label', 'description' ), array( $langCode ) );

		//TODO: apply language fallback
		$propertyLabels = array();
		foreach ( $entities as $id => $entity ) {
			if ( isset( $entity['labels'][$langCode] ) ) {
				$label = $entity['labels'][$langCode]['value'];
				$propertyLabels[$id] = $label;
			}
		}

		wfProfileOut( __METHOD__ );
		return $propertyLabels;
	}

	/**
	 * Wraps each record in $entities with revision info, similar to how EntityRevisionSerializer
	 * does this.
	 *
	 * @todo: perhaps move this into EntityInfoBuilder; Note however that it is useful to be
	 * able to pick which information is actually needed in which context. E.g. we are skipping the
	 * actual revision ID here, and thereby avoiding any database access.
	 *
	 * @param array $entities A list of entity records
	 *
	 * @return array A list of revision records
	 */
	private function attachRevisionInfo( array $entities ) {
		$idParser = $this->idParser;
		$titleLookup = $this->entityTitleLookup;

		return array_map( function( $entity ) use ( $idParser, $titleLookup ) {
				$id = $idParser->parse( $entity['id'] );

				// If the title lookup needs DB access, we really need a better way to do this!
				$title = $titleLookup->getTitleForId( $id );

				return array(
					'content' => $entity,
					'title' => $title->getPrefixedText(),
					//'revision' => 0,
				);
			},
			$entities );
	}

	/**
	 * Returns a new view which is suited for rendering the given entity type
	 *
	 * @since 0.2
	 *
	 * @param string $type The entity type, e.g. Item::ENTITY_TYPE.
	 * @param SnakFormatter      $snakFormatter
	 * @param Lib\PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param IContextSource|null $context
	 * @param LanguageFallbackChain|null $languageFallbackChain Overrides any language fallback chain created inside, for testing
	 *
	 * @throws \MWException
	 * @return EntityView
	 */
	public static function newForEntityType(
		$type,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		IContextSource $context = null,
		LanguageFallbackChain $languageFallbackChain = null
	) {
		if ( !in_array( $type, array_keys( self::$typeMap ) ) ) {
			throw new MWException( "No entity view known for handling entities of type '$type'" );
		}

		if ( !$context ) {
			$context = \RequestContext::getMain();
		}

		if ( !$languageFallbackChain ) {
			$factory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
			if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
				$languageFallbackChain = $factory->newFromContextForPageView( $context );
			} else {
				# Effectively disables fallback.
				$languageFallbackChain = $factory->newFromLanguage(
					$context->getLanguage(), LanguageFallbackChainFactory::FALLBACK_SELF
				);
			}
		}

		$idParser = new BasicEntityIdParser();

		$class = self::$typeMap[ $type ];
		$instance = new $class(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityTitleLookup,
			$idParser,
			$languageFallbackChain );

		return $instance;
	}
}
