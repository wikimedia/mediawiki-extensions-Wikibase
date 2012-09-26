<?php

namespace Wikibase;
use Html, ParserOptions, ParserOutput, Title, Language, IContextSource, OutputPage, Sites, Site, MediaWikiSite;

/**
 * Base class for creating views for all different kinds of Wikibase\Entity.
 * For the Wikibase\Entity this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @todo  We might want to re-design this at a later point, designing this as a more generic and encapsulated rendering
 *        of DataValue instances instead of having functions here for generating different parts of the HTML. Right now
 *        these functions require an EntityContent while a DataValue (if it were implemented) should be sufficient.
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 * @author Daniel Kinzler
 */
abstract class EntityView extends \ContextSource {

	const VIEW_TYPE = 'entity';

	/**
	 * Maps entity types to the corresponding entity view.
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	public static $typeMap = array(
		Item::ENTITY_TYPE => '\Wikibase\ItemView',
		Property::ENTITY_TYPE => '\Wikibase\PropertyView',
		Query::ENTITY_TYPE => '\Wikibase\QueryView'
	);

	/**
	 * Constructor.
	 *
	 * @todo think about using IContextSource here. Parser for example uses parser options (which also can be generated
	 *       from an IContextSource) but this seems sufficient for now.
	 *
	 * @since 0.1
	 *
	 * @param \IContextSource|null $context
	 */
	public function __construct( IContextSource $context = null ) {
		if ( !$context ) {
			$context = \RequestContext::getMain();
		}
		$this->setContext( $context );
	}

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity the entity to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtml( EntityContent $entity, Language $lang = null, $editable = true ) {
		//NOTE: even though $editable is unused at the moment, we will need it for the JS-less editing model.
		$info = $this->extractEntityInfo( $entity );
		$entityType = static::VIEW_TYPE;
		$entityId = $entity->getEntity()->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes
		$html = '';

		$html .= Html::openElement(
			'div',
			array(
				'id' => "wb-$entityType-$entityId",
				'class' => "wb-entity wb-$entityType",
				'lang' => $info['lang']->getCode(),
				'dir' => $info['lang']->getDir()
			)
		);

		$html .= $this->getInnerHtml( $entity, $lang, $editable );

		// container reserved for widgets, will be displayed on the right side if there is space
		// TODO: no point in inserting this here, is there? Should be generated in JS!
		$html .= Html::element( 'div',
			array(
				'id' => "wb-widget-container-$entityId",
				'class' => 'wb-widget-container'
			)
		);

		return $html . Html::closeElement( 'div' );
	}

	/**
	 * Builds and returns the inner HTML for representing a whole WikibaseEntity. The difference to getHtml() is that
	 * this does not group all the HTMl within one parent node as one entity.
	 *
	 * @string
	 *
	 * @param EntityContent $entity
	 * @param \Language $lang
	 * @param bool $editable
	 * @return array
	 */
	public function getInnerHtml( EntityContent $entity, Language $lang = null, $editable = true ) {
		$html = '';

		//label:
		$html .= $this->getHtmlForLabel( $entity, $lang, $editable );

		// description:
		// even if description is empty, nodes have to be inserted as placeholders for an input box
		$html .= $this->getHtmlForDescription( $entity, $lang, $editable );

		// aliases:
		$html .= $this->getHtmlForAliases( $entity, $lang, $editable );

		return $html;
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
	 * @param EntityContent       $entity the entity to analyze/render
	 * @param null|ParserOptions  $options parser options. If nto provided, the local context will be used to create generic parser options.
	 * @param bool                $generateHtml whether to generate HTML. Set to false if only interested in meta-info. default: true.
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityContent $entity, ParserOptions $options = null, $generateHtml = true ) {
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

		if ( $generateHtml ) {
			$html = $this->getHtml( $entity, $langCode, $editable );
			$pout->setText( $html );
		}

		#@todo (phase 2) would be nice to put pagelinks (entity references) and categorylinks (from special properties)...
		#@todo:          ...as well as languagelinks/sisterlinks into the ParserOutput.

		// make css available for JavaScript-less browsers
		$pout->addModuleStyles( array( 'wikibase.common' ) );

		// make sure required client sided resources will be loaded:
		$pout->addModules( 'wikibase.ui.PropertyEditTool' );

		//FIXME: some places, like Special:CreateItem, don't want to override the page title.
		//       But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//       So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		return $pout;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's label.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity the entity to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForLabel( EntityContent $entity, Language $lang = null, $editable = true ) {
		$info = $this->extractEntityInfo( $entity );
		$html = Html::openElement( 'h1',
			array(
				'id' => 'wb-firstHeading-' . $info['id'],
				'class' => 'wb-firstHeading wb-value-row'
			)
		);
		$html .= Html::element(
			'span',
			array( 'dir' => 'auto' ),
			$entity->getEntity()->getLabel( $info['lang']->getCode() )
		);
		return $html . Html::closeElement( 'h1' );
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's description.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity the entity to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForDescription( EntityContent $entity, Language $lang = null, $editable = true ) {
		$info = $this->extractEntityInfo( $entity );
		$description = $entity->getEntity()->getDescription( $info['lang']->getCode() );

		$html = Html::openElement( 'div',
			array(
				'dir' => 'auto',
				'class' => 'wb-property-container wb-value-row'
			)
		);
		$html .= Html::element( 'div', array( 'class' => 'wb-property-container-key', 'title' => 'description' ) );
		$html .= Html::element( 'span', array( 'class' => 'wb-property-container-value'), $description );
		$html .= Html::closeElement( 'div' );

		$html .= Html::element( 'hr', array( 'class' => 'wb-hr' ) );
		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's aliases.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity the entity to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForAliases( EntityContent $entity, Language $lang = null, $editable = true ) {
		/*
		 * add an h1 for displaying the entity's label; the actual firstHeading is being hidden by css since the
		 * original MediaWiki DOM does not represent a Wikidata entity's structure where the combination of label and
		 * description is the unique "title" of an entity which should not be semantically disconnected by having
		 * elements in between, like siteSub, contentSub and jump-to-nav
		 */
		$info = $this->extractEntityInfo( $entity );
		$aliases = $entity->getEntity()->getAliases( $info['lang']->getCode() );
		$html = '';

		if( empty( $aliases ) ) {
			// no aliases available for this entity
			$html .= Html::element( 'div', array( 'class' => 'wb-aliases-empty' ), wfMessage( 'wikibase-aliases-empty' ) );
		} else {
			$html .= Html::openElement( 'div', array( 'class' => 'wb-aliases' ) );
			$html .= Html::element( 'span', array( 'class' => 'wb-aliases-label' ), wfMessage( 'wikibase-aliases-label' )->text() );
			$html .= Html::openElement( 'ul', array( 'class' => 'wb-aliases-container' ) );
			foreach( $aliases as $alias ) {
				$html .= Html::element(
					'li', array( 'class' => 'wb-aliases-alias' ), $alias
				);
			}
			$html .= Html::closeElement( 'ul' );
			$html .= Html::closeElement( 'div' );
		}
		return $html;
	}

	/**
	 * Helper function returning language and id information bundled in an array.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity
	 * @param Language|null $lang
	 * @return array
	 */
	protected function extractEntityInfo( EntityContent $entity, Language $lang = null ) {
		if( !$lang ) {
			$lang = $this->getLanguage();
		}
		return array(
			'lang' => $lang,
			'id' => $entity->getEntity()->getId()
		);
	}

	/**
	 * Outputs the given entity to the OutputPage.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent      $entity the entity to output
	 * @param null|OutputPage    $out the output page to write to. If not given, the local context will be used.
	 * @param null|ParserOptions $options parser options to use for rendering. If not given, the local context will be used.
	 * @param null|ParserOutput  $pout optional parser object - provide this if you already have a parser options for
	 *                           this entity, to avoid redundant rendering.
	 * @return ParserOutput the parser output, for further processing.
	 *
	 * @todo: fixme: currently, only one entity can be shown per page, because the entity's id is in a global JS config variable.
	 */
	public function render( EntityContent $entity, OutputPage $out = null, ParserOptions $options = null, ParserOutput $pout = null ) {
		$isPoutSet = $pout !== null;

		if ( !$out ) {
			$out = $this->getOutput();
		}

		if ( !$pout ) {
			if ( !$options ) {
				$options = $this->makeParserOptions();
			}

			$pout = $this->getParserOutput( $entity, $options, true );
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
		static::registerJsConfigVars( $out, $entity, $langCode, $editableView ); //XXX: $editableView should *not* reflect user permissions

		$out->addParserOutput( $pout );
		return $pout;
	}

	/**
	 * Helper function for registering any JavaScript stuff needed to show the entity.
	 * @todo Would be much nicer if we could do that via the ResourceLoader Module or via some hook.
	 *
	 * @since 0.1
	 *
	 * @param OutputPage    $out the OutputPage to add to
	 * @param EntityContent $entity the entity for which we want to add the JS config
	 * @param string        $langCode the language used for showing the entity.
	 * @param bool          $editableView whether entities on this page should be editable.
	 *                      This is independent of user permissions.
	 *
	 * @todo: fixme: currently, only one entity can be shown per page, because the entity's id is in a global JS config variable.
	 */
	public static function registerJsConfigVars( OutputPage $out, EntityContent $entity, $langCode, $editableView = false  ) {
		global $wgUser;

		//TODO: replace wbUserIsBlocked this with more useful info (which groups would be required to edit? compare wgRestrictionEdit and wgRestrictionCreate)
		$out->addJsConfigVars( 'wbUserIsBlocked', $wgUser->isBlockedFrom( $entity->getTitle() ) ); //NOTE: deprecated

		// tell JS whether the user can edit
		$out->addJsConfigVars( 'wbUserCanEdit', $entity->userCanEdit( $wgUser, false ) ); //TODO: make this a per-entity info
		$out->addJsConfigVars( 'wbIsEditView', $editableView );  //NOTE: page-wide property, independent of user permissions

		// hand over the entity's ID to JS
		$out->addJsConfigVars( 'wbEntityId', $entity->getEntity()->getId() );
		$out->addJsConfigVars( 'wbEntityType', static::VIEW_TYPE ); //TODO: use $entity->getEntity()->getType after prefixes got removed there
		$out->addJsConfigVars( 'wbDataLangName', Utils::fetchLanguageName( $langCode ) );
	}


	/**
	 * Returns a new view which is suited to render different variations of EntityContent.
	 *
	 * @param EntityContent $entity
	 * @return mixed
	 * @throws \MWException
	 *
	 * @since 0.2
	 */
	public static function newForEntityContent( EntityContent $entity ) {
		$type = $entity->getEntity()->getType();

		if ( !in_array( $type, array_keys( self::$typeMap ) ) ) {
			throw new \MWException( "No entity view known for handling entities of type '$type'" );
		}

		$instance = new self::$typeMap[ $type ]();
		return $instance;
	}
}
