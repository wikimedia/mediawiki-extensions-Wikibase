<?php

namespace Wikibase;

use Html;
use ParserOutput;
use Language;
use IContextSource;
use OutputPage;
use FormatJson;
use User;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
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
	 * @var EntityLookup
	 */
	protected $entityLookup;

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
	 * @var SectionEditLinkGenerator
	 */
	protected $sectionEditLinkGenerator;

	/**
	 * @var TextInjector
	 */
	protected $injector;

	/**
	 * @var string
	 */
	protected $rightsUrl;

	/**
	 * @var string
	 */
	protected $rightsText;

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
	 * @since    0.1
	 *
	 * @param IContextSource|null $context
	 * @param SnakFormatter $snakFormatter
	 * @param Lib\PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityLookup $entityLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param string $rightsUrl
	 * @param string $rightsText
	 *
	 * @throws \InvalidArgumentException
	 * @todo: move the $editable flag here, instead of passing it around everywhere
	 *
	 */
	public function __construct(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		LanguageFallbackChain $languageFallbackChain,
		$rightsUrl = null,
		$rightsText = null
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
		$this->entityLookup = $entityLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->idParser = $idParser;
		$this->languageFallbackChain = $languageFallbackChain;

		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
		$this->injector = new TextInjector();

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->rightsUrl = $rightsUrl !== null ? $rightsUrl : $settings->get( 'dataRightsUrl' );
		$this->rightsText = $rightsText !== null ? $rightsText : $settings->get( 'dataRightsText' );
	}

	/**
	 * Resets the placeholders managed by this view
	 */
	public function resetPlaceholders() {
		$this->injector = new TextInjector();
	}

	/**
	 * Returns the placeholder map build while generating HTML.
	 * The map returned here may be used with TextInjector.
	 *
	 * @return array string -> array
	 */
	public function getPlaceholders() {
		return $this->injector->getMarkers();
	}

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @note: The HTML returned by this method may contain placeholders. Such placeholders can be
	 * expanded with the help of TextInjector::inject() calling back to
	 * EntityViewPlaceholderExpander::getExtraUserLanguages()
	 * @note: In order to keep the list of placeholders small, this calls resetPlaceholders().
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string HTML
	 */
	public function getHtml( EntityRevision $entityRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$this->resetPlaceholders();

		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.

		$lang = $this->getLanguage();
		$entityId = $entityRevision->getEntity()->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes
		$html = '';

		$html .= wfTemplate( 'wb-entity',
			$entityRevision->getEntity()->getType(),
			$entityId,
			$lang->getCode(),
			$lang->getDir(),
			$this->getInnerHtml( $entityRevision, $editable )
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
	 * @param bool $editable
	 * @return string
	 */
	public function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$claims = '';

		if ( $entityRevision->getEntity()->getType() === 'item' ) {
			$claims = $this->getHtmlForClaims( $entityRevision->getEntity(), $editable );
		}

		if ( $entityRevision->getEntity()->getId() ) {
			// Placeholder for a termbox for the present item.
			// EntityViewPlaceholderExpander must know about the parameters used here.
			$languageTerms = $this->injector->newMarker( 'termbox', $entityRevision->getEntity()->getId()->getSerialization() );
		} else {
			//NOTE: this should only happen during testing
			$languageTerms = '';
		}

		$html = wfTemplate( 'wb-entity-content',
			$this->getHtmlForLabel( $entityRevision->getEntity(), $editable ),
			$this->getHtmlForDescription( $entityRevision->getEntity(), $editable ),
			$this->getHtmlForAliases( $entityRevision->getEntity(), $editable ),
			$this->getHtmlForToc(),
			$languageTerms,
			$claims
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the html for the toc.
	 *
	 * @return string
	 */
	public function getHtmlForToc() {
		$tocContent = '';
		$tocSections = $this->getTocSections();

		if ( count( $tocSections ) < 2 ) {
			// Including the marker for the termbox toc entry, there is fewer
			// 3 sections. MediaWiki core doesn't show a TOC unless there are
			// at least 3 sections, so we shouldn't either.
			return '';
		}

		// Placeholder for the TOC entry for the term box (which may or may not be used for a given user).
		// EntityViewPlaceholderExpander must know about the 'termbox-toc' name.
		$tocContent .= $this->injector->newMarker( 'termbox-toc' );

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
	 * @return array( link target => system message key )
	 */
	protected function getTocSections() {
		return array();
	}

	/**
	 * Renders an entity into an ParserOutput object
	 *
	 * @since 0.1
	 *
	 * @param EntityRevision $entityRevision the entity to analyze/render
	 * @param bool $editable whether to make the page's content editable
	 * @param bool $generateHtml whether to generate HTML. Set to false if only interested in meta-info. default: true.
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityRevision $entityRevision, $editable = true, $generateHtml = true ) {
		wfProfileIn( __METHOD__ );

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
			$html = $this->getHtml( $entityRevision, $editable );
			$pout->setText( $html );
			$pout->setExtensionData( 'wikibase-view-chunks', $this->getPlaceholders() );
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
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForLabel( Entity $entity, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$lang = $this->getLanguage();

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
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForDescription( Entity $entity, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$lang = $this->getLanguage();
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
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForAliases( Entity $entity, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$lang = $this->getLanguage();

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
	 * Builds and returns the HTML representing a WikibaseEntity's claims.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForClaims( Entity $entity, $editable = true ) {
		wfProfileIn( __METHOD__ );

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

		$propertyLabels = $this->getPropertyLabels( $entity, $this->getLanguage()->getCode() );

		/**
		 * @var string $claimsHtml
		 * @var Claim[] $claims
		 */
		$claimsHtml = '';
		foreach( $claimsByProperty as $claims ) {
			$propertyHtml = '';

			$propertyId = $claims[0]->getMainSnak()->getPropertyId();
//			$propertyKey = $propertyId->getSerialization();
//			$propertyLabel = isset( $propertyLabels[$propertyKey] )
//				? $propertyLabels[$propertyKey]
//				: $propertyKey;
//			$propertyLink = \Linker::link(
//				$this->entityTitleLookup->getTitleForId( $propertyId ),
//				htmlspecialchars( $propertyLabel )
//			);
			$options = new FormatterOptions();
			$f = new EntityIdHtmlLinkFormatter( $options, $this->entityLookup, $this->entityTitleLookup );
			$propertyLink = $f->format( $propertyId );

			$htmlForEditSection = $this->getHtmlForEditSection( '', 'span' ); // TODO: add link to SpecialPage

			$claimHtmlGenerator = new ClaimHtmlGenerator(
				$this->snakFormatter,
				$this->entityLookup,
				$this->entityTitleLookup,
				$propertyLabels
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
	protected function getHtmlForEditSection( $url, $tag = 'span', $action = 'edit', $enabled = true ) {
		$key = $action === 'add' ? 'wikibase-add' : 'wikibase-edit';
		$msg = $this->getContext()->msg( $key );

		return $this->sectionEditLinkGenerator->getHtmlForEditSection( $url, $msg, $tag, $enabled );
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
			$subpage = $this->getFormattedIdForEntity( $entity );
		} else {
			$subpage = ''; // can't skip this, that would confuse the order of parameters!
		}

		if ( $lang !== null ) {
			$subpage .= '/' . $lang->getCode();
		}
		return $specialpage->getPageTitle( $subpage )->getLocalURL();
	}

	/**
	 * Helper function for registering any JavaScript stuff needed to show the entity.
	 * @todo Would be much nicer if we could do that via the ResourceLoader Module or via some hook.
	 * @todo ...or at least stuff this information into ParserOutput, so it would get cached
	 *
	 * @since 0.1
	 *
	 * @param OutputPage    $out the OutputPage to add to
	 * @param EntityRevision  $entityRevision the entity for which we want to add the JS config
	 * @param bool           $editableView whether entities on this page should be editable.
	 *                       This is independent of user permissions.
	 *
	 * @todo: fixme: currently, only one entity can be shown per page, because the entity's id is in a global JS config variable.
	 */
	public function registerJsConfigVars( OutputPage $out, EntityRevision $entityRevision, $editableView = false  ) {
		wfProfileIn( __METHOD__ );

		$langCode = $this->getLanguage()->getCode();
		$user = $this->getUser();
		$entity = $entityRevision->getEntity();
		$title = $this->entityTitleLookup->getTitleForId( $entityRevision->getEntity()->getId() );

		//TODO: replace wbUserIsBlocked this with more useful info (which groups would be required to edit? compare wgRestrictionEdit and wgRestrictionCreate)
		$out->addJsConfigVars( 'wbUserIsBlocked', $user->isBlockedFrom( $title ) ); //NOTE: deprecated

		// tell JS whether the user can edit
		$out->addJsConfigVars( 'wbUserCanEdit', $title->userCan( 'edit', $user, false ) ); //TODO: make this a per-entity info
		$out->addJsConfigVars( 'wbIsEditView', $editableView );  //NOTE: page-wide property, independent of user permissions

		// used by gadgets in wikidata
		$out->addJsConfigVars( 'wbEntityType', $entity->getType() );

		// entity specific data
		$out->addJsConfigVars( 'wbEntityId', $this->getFormattedIdForEntity( $entity ) );

		$copyrightMessageBuilder = new CopyrightMessageBuilder();
		$copyrightMessage = $copyrightMessageBuilder->build(
			$this->rightsUrl,
			$this->rightsText,
			$this->getLanguage()
		);

		// copyright warning message
		$out->addJsConfigVars( 'wbCopyright', array(
			'version' => $this->getContext()->msg( 'wikibase-shortcopyrightwarning-version' )->parse(),
			'messageHtml' => $copyrightMessage->inLanguage( $this->getLanguage() )->parse()
		) );

		$experimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$out->addJsConfigVars( 'wbExperimentalFeatures', $experimental );

		// TODO: use injected id formatter
		$serializationOptions = new SerializationOptions();
		$serializationOptions->setLanguages( Utils::getLanguageCodes() + array( $langCode => $this->languageFallbackChain ) );

		$serializerFactory = new SerializerFactory( $serializationOptions );
		$serializer = $serializerFactory->newSerializerForObject( $entity, $serializationOptions );

		$entityData = $serializer->getSerialized( $entity );

		$out->addJsConfigVars(
			'wbEntity',
			FormatJson::encode( $entityData )
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
}
