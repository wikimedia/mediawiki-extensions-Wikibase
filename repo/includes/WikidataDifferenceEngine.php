<?php

class WikidataDifferenceEngine extends DifferenceEngine {

	function __construct( $context = null, $old = 0, $new = 0, $rcid = 0, $refreshCache = false, $unhide = false ) {
		parent::__construct($context, $old, $new, $rcid, $refreshCache, $unhide);

		$this->mRefreshCache = true; #FIXME: debug only!
	}

	function generateContentDiff( Content $old, Content $new ) {
		wfProfileIn( __METHOD__ );

		$aold = WikidataContentHandler::flattenArray( $old->getNativeData() );
		$anew = WikidataContentHandler::flattenArray( $new->getNativeData() );

		$keys = array_unique( array_merge( array_keys( $aold ), array_keys( $anew ) ) );

		$edits = array();

		foreach ( $keys as $k ) {
			$lold = empty( $aold[$k] ) ? null : array( $k . ": " . $aold[$k] );
			$lnew = empty( $anew[$k] ) ? null : array( $k . ": " . $anew[$k] );

			if ( !$lold && $lnew ) $e = new _DiffOp_Add( $lnew );
			else if ( $lold && !$lnew ) $e = new _DiffOp_Delete( $lold );
			else if ( $aold[$k] !== $anew[$k] ) $e = new _DiffOp_Change( $lold, $lnew );
			else $e = new _DiffOp_Copy( $lold );

			$edits[] = $e;
		}

		$res = new DiffResult( $edits );

		wfProfileOut( __METHOD__ );
		return $res;
	}

	function generateContentDiffBody( Content $old, Content $new ) {
		global $wgContLang;

		wfProfileIn( __METHOD__ );

		$res = $this->generateContentDiff( $old, $new );

		$formatter = new TableDiffFormatter();
		$difftext = $wgContLang->unsegmentForDiff( $formatter->format( $res ) ) .

		wfProfileOut( __METHOD__ );

		return $difftext;
	}

}
