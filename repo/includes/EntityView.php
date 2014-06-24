<?php

namespace Wikibase;

use ContextSource;
use Html;
use IContextSource;
use InvalidArgumentException;
use ParserOutput;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;
use Wikibase\Repo\View\TextInjector;

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
abstract class EntityView extends ContextSource {

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
	 * @var SectionEditLinkGenerator
	 */
	protected $sectionEditLinkGenerator;

	/**
	 * @var TextInjector
	 */
	protected $textInjector;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	protected $configBuilder;

	/**
	 * @var ClaimHtmlGenerator
	 */
	protected $claimHtmlGenerator;

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
	 * @since 0.1
	 *
	 * @param IContextSource|null $context
	 * @param SnakFormatter $snakFormatter
	 * @param Lib\PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializationOptions $options
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 *
	 * @todo: move the $editable flag here, instead of passing it around everywhere
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		SerializationOptions $options,
		ParserOutputJsConfigBuilder $configBuilder
	) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
				&& $snakFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_WIDGET ) {
			throw new InvalidArgumentException( '$snakFormatter is expected to return text/html, not '
					. $snakFormatter->getFormat() );
		}

		$this->setContext( $context );
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityInfoBuilder = $entityInfoBuilder;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->options = $options;
		$this->configBuilder = $configBuilder;

		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
		$this->textInjector = new TextInjector();

		// @todo inject in constructor
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup
		);

		$this->claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$entityTitleLookup
		);
	}

	/**
	 * Resets the placeholders managed by this view
	 */
	public function resetPlaceholders() {
		$this->textInjector = new TextInjector();
	}

	/**
	 * Returns the placeholder map build while generating HTML.
	 * The map returned here may be used with TextInjector.
	 *
	 * @return array string -> array
	 */
	public function getPlaceholders() {
		return $this->textInjector->getMarkers();
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

		// Show loading spinner as long as JavaScript is initialising.
		// The fastest way to show it is placing the script right after the corresponding HTML.
		// Remove it after a while in any case (e.g. some resources might not have been loaded
		// silently, so JavaScript is not initialising).
		// Additionally attaching to window.error would only make sense before any other
		// JavaScript is parsed.
		$html .= Html::inlineScript( '
if ( $ ) {
	$( ".wb-entity" ).addClass( "loading" ).after( function() {
		var $div = $( "<div/>" ).addClass( "wb-entity-spinner mw-small-spinner" );
		$div.css( "top", $div.height() + "px" );
		$div.css(
			"' . ( $lang->isRTL() ? 'right' : 'left' ) . '",
			( ( $( this ).width() - $div.width() ) / 2 | 0 ) + "px"
		);
		return $div;
	} );
	window.setTimeout( function() {
		$( ".wb-entity" ).removeClass( "loading" );
		$( ".wb-entity-spinner" ).remove();
	}, 7000 );
}
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

		$entity = $entityRevision->getEntity();

		$html = '';

		$html .= $this->getHtmlForLabel( $entity, $editable );
		$html .= $this->getHtmlForDescription( $entity, $editable );

		$html .= wfTemplate( 'wb-entity-header-separator' );

		$html .= $this->getHtmlForAliases( $entity, $editable );
		$html .= $this->getHtmlForToc();
		$html .= $this->getHtmlForTermBox( $entityRevision, $editable );
		$html .= $this->getHtmlForClaims( $entity, $editable );

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
		$tocContent .= $this->textInjector->newMarker( 'termbox-toc' );

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
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 *
	 * @return string
	 */
	protected function getHtmlForTermBox( EntityRevision $entityRevision, $editable = true ) {
		if ( $entityRevision->getEntity()->getId() ) {
			// Placeholder for a termbox for the present item.
			// EntityViewPlaceholderExpander must know about the parameters used here.
			return $this->textInjector->newMarker(
				'termbox',
				$entityRevision->getEntity()->getId()->getSerialization(),
				$entityRevision->getRevision()
			);
		}

		return '';
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
	public function getParserOutput( EntityRevision $entityRevision, $editable = true,
		$generateHtml = true
	) {
		wfProfileIn( __METHOD__ );

		// fresh parser output with entity markup
		$pout = new ParserOutput();

		$entity =  $entityRevision->getEntity();
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;

		$configVars = $this->configBuilder->build( $entity, $this->options, $isExperimental );
		$pout->addJsConfigVars( $configVars );

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
		//	 But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	 So, for now, we leave it to the caller to override the display title, if desired.
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
		$editUrl = $this->sectionEditLinkGenerator->getEditUrl( 'SetLabel', $entity, $lang );
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
		$editUrl = $this->sectionEditLinkGenerator->getEditUrl( 'SetDescription', $entity, $lang );

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
		$editUrl = $this->sectionEditLinkGenerator->getEditUrl( 'SetAliases', $entity, $lang );

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
	 * Returns the HTML for the heading of the claims section
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 * @param bool $editable
	 *
	 * @return string
	 */
	protected function getHtmlForClaimsSectionHeading( Entity $entity, $editable = true ) {
		$html = wfTemplate(
			'wb-section-heading',
			wfMessage( 'wikibase-claims' ),
			'claims' // ID - TODO: should not be added if output page is not the entity's page
		);

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

		$html = $this->getHtmlForClaimsSectionHeading( $entity, $editable );

		// aggregate claims by properties
		$claimsByProperty = array();
		foreach( $claims as $claim ) {
			$propertyId = $claim->getMainSnak()->getPropertyId();
			$claimsByProperty[$propertyId->getNumericId()][] = $claim;
		}

		$entityInfo = $this->getEntityInfo( $entity, $this->getLanguage()->getCode() );

		/**
		 * @var string $claimsHtml
		 * @var Claim[] $claims
		 */
		$claimsHtml = '';

		foreach( $claimsByProperty as $claims ) {
			$propertyHtml = '';

			$propertyId = $claims[0]->getMainSnak()->getPropertyId();
			$key = $propertyId->getSerialization();
			$propertyLabel = $key;
			if ( isset( $entityInfo[$key] ) && !empty( $entityInfo[$key]['labels'] ) ) {
				$entityInfoLabel = reset( $entityInfo[$key]['labels'] );
				$propertyLabel = $entityInfoLabel['value'];
			}

			$propertyLink = \Linker::link(
				$this->entityTitleLookup->getTitleForId( $propertyId ),
				htmlspecialchars( $propertyLabel )
			);

			$htmlForEditSection = $this->getHtmlForEditSection( '', 'span' ); // TODO: add link to SpecialPage

			foreach( $claims as $claim ) {
				$propertyHtml .= $this->claimHtmlGenerator->getHtmlForClaim(
					$claim,
					$entityInfo,
					$htmlForEditSection
				);
			}

			$toolbarHtml = wfTemplate( 'wikibase-toolbar',
				'wb-addtoolbar',
				// TODO: add link to SpecialPage
				$this->getHtmlForEditSection( '', 'span', 'add' )
			);

			$claimsHtml .= wfTemplate( 'wb-claimlistview',
				$propertyHtml,
				wfTemplate( 'wb-claimgrouplistview-groupname', $propertyLink ) . $toolbarHtml,
				$propertyId->getSerialization()
			);

		}

		$claimgrouplistviewHtml = wfTemplate( 'wb-claimgrouplistview', $claimsHtml, '' );

		// TODO: Add link to SpecialPage that allows adding a new claim.
		$html = $html . wfTemplate( 'wb-claimlistview', $claimgrouplistviewHtml, '', '' );

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
	private function getHtmlForEditSection( $url, $tag = 'span', $action = 'edit', $enabled = true ) {
		$key = $action === 'add' ? 'wikibase-add' : 'wikibase-edit';
		$msg = $this->getContext()->msg( $key );

		return $this->sectionEditLinkGenerator->getHtmlForEditSection( $url, $msg, $tag, $enabled );
	}

	/**
	 * Fetches labels and descriptions for all entities used as properties in snaks in the given
	 * entity.
	 *
	 * @param Entity $entity
	 * @param string $languageCode the language code of the labels to fetch.
	 *
	 * @return array[] Entity info array that maps property IDs to labels and descriptions.
	 */
	protected function getEntityInfo( Entity $entity, $languageCode ) {
		wfProfileIn( __METHOD__ );
		// TODO: Share cache with PropertyLabelResolver
		// TODO: ... or share info with getBasicEntityInfo.

		// TODO: Make a finder just for properties, so we don't have to filter.
		$refFinder = new ReferencedEntitiesFinder();
		$entityIds = $refFinder->findSnakLinks( $entity->getAllSnaks() );
		$propertyIds = array_filter( $entityIds, function ( EntityId $id ) {
			return $id->getEntityType() === Property::ENTITY_TYPE;
		} );

		// NOTE: This is a bit hackish, it would be more appropriate to use a TermTable here.
		$entityInfo = $this->entityInfoBuilder->buildEntityInfo( $propertyIds );
		$this->entityInfoBuilder->removeMissing( $entityInfo );
		$this->entityInfoBuilder->addTerms(
			$entityInfo,
			array( 'label', 'description' ),
			array( $languageCode )
		);

		wfProfileOut( __METHOD__ );
		return $entityInfo;
	}

}
