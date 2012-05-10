<?php

/**
 * Represents a diff between two WikibaseEntity instances.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class WikibaseEntityDiff extends ContentDiff {



}

/**
 * Represents a difference of a Content.
 *
 * @since WD.diff
 * TODO: move to core
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IContentDiff {

	//static function newFromContents( Content $content0, Content $content1 );

	// TODO: something to get IContextSource deriving display class

}

/**
 * Represents a difference of a Content.
 *
 * @since WD.diff
 * TODO: move to core
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ContentDiff implements IContentDiff {

	public function __construct( Content $oldContent, Content$newContent ) {

	}

}