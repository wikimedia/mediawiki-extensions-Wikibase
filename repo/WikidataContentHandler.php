<?php
require_once("includes/diff/DairikiDiff.php"); #FIXME: using private stuff from Dairiki!

class WikidataContentHandler extends ContentHandler {

	public function getDifferenceEngine(IContextSource $context, $old = 0, $new = 0, $rcid = 0,
										$refreshCache = false, $unhide = false) {

		return new WikidataDifferenceEngine($context, $old, $new, $rcid, $refreshCache, $unhide);
	}

	public function __construct() {
		$formats = array(
			'application/json',
			'application/vnd.php.serialized' #FIXME: find out what mime type the api uses for serialized php objects
		);

		parent::__construct( CONTENT_MODEL_WIKIDATA, $formats );
	}

    public function createArticle( Title $title ) {
//        $this->checkModelName( $title->getContentModelName() );

        $article = new WikidataPage( $title );
        return $article;
    }

	public function getDefaultFormat() {
		global $wgWikidataSerialisationFormat;

		return $wgWikidataSerialisationFormat;
	}

	/**
	 * @param WikidataContent $content
	 * @param null|String $format
	 * @return String
	 */
	public function serialize(Content $content, $format = null)
	{
		global $wgWikidataSerialisationFormat;

		if ( !$format ) $format = $wgWikidataSerialisationFormat;

		#FIXME: assert $content is a WikidataContent instance
		$data = $content->getNativeData();

		if ( $format == 'application/vnd.php.serialized' ) $blob = serialize( $data );
		else if ( $format == 'application/json' ) $blob = json_encode( $data );
		else throw new MWException( "serialization format $format is not supported for Wikidata content model" );

		return $blob;
	}

	/**
	 * @param $blob String
	 * @param null|String $format
	 * @return WikidataContent
	 */
	public function unserialize($blob, $format = null)
	{
		global $wgWikidataSerialisationFormat;

		if ( !$format ) $format = $wgWikidataSerialisationFormat;

		if ( $format == 'application/vnd.php.serialized' ) $data = unserialize( $blob ); #FIXME: suppress notice on failed serialization!
		else if ( $format == 'application/json' ) $data = json_decode( $blob, true ); #FIXME: suppress notice on failed serialization!
		else throw new MWException( "serialization format $format is not supported for Wikidata content model" );

		if ( $data === false || $data === null ) throw new MWContentSerializationException("failed to deserialize");

		return new WikidataContent( $data );
	}

	/**
	 * @return WikidataContent
	 */
	public function emptyContent()
	{
		$data = array();
		return new WikidataContent( $data );
	}

    public static function flattenArray( $a, $prefix = '', &$into = null) {
        if ( $into === null ) $into = array();

        foreach ( $a as $k => $v ) {
            if ( is_object( $v ) ) {
                $v = get_object_vars( $v );
            }

            if ( is_array( $v ) ) {
                WikidataContentHandler::flattenArray( $v, "$prefix$k | ", $into );
            } else {
                $into[ "$prefix$k" ] = $v;
            }
        }

        return $into;
    }
}

class WikidataDifferenceEngine extends DifferenceEngine {
    function __construct($context = null, $old = 0, $new = 0, $rcid = 0,
                         $refreshCache = false, $unhide = false)
    {
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

    /*
    function generateContentDiffBody( Content $old, Content $new ) {
        $only_in_old = WikidataDifferenceEngine::arrayRecursiveDiff( $old->getNativeData(), $new->getNativeData() );
        $only_in_new = WikidataDifferenceEngine::arrayRecursiveDiff( $new->getNativeData(), $old->getNativeData() );

        $left = Html::element('pre', null, print_r( $only_in_old, true ) );
        $right = Html::element('pre', null, print_r( $only_in_new, true ) );

        return ...;
    }
    */

    /*
	protected static function arrayRecursiveDiff($aArray1, $aArray2) {
		$aReturn = array();

		foreach ($aArray1 as $mKey => $mValue) {
			if (array_key_exists($mKey, $aArray2)) {
				if (is_array($mValue)) {
					$aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
					if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
				} else {
					if ($mValue != $aArray2[$mKey]) {
						$aReturn[$mKey] = $mValue;
					}
				}
			} else {
				$aReturn[$mKey] = $mValue;
			}
		}
		return $aReturn;
	}
    */

	/*
	 * public function array_diff_multidimensional($arr1, $arr2) {
			$answer = array();
			foreach($arr1 as $k1 => $v1) {
				// is the key present in the second array?
				if (!array_key_exists($k1, $arr2)) {
				   $answer[$k1] = $v1;
				   continue;
				}

				// PHP makes all arrays into string "Array", so if both items
				// are arrays, recursively test them before the string check
				if (is_array($v1) && is_array($arr2[$k1])) {
					$answer[$k1] = array_diff_multidimensional($v1, $arr2[$k1]);
					continue;
				}

				// do the array_diff string check
				if ((string)$arr1[$k1] === (string)$arr2[$k1]) {
					continue;
				}

				// since both values are not arrays, and they don't match,
				// simply add the $arr1 value to match the behavior of array_diff
				// in the PHP core
				$answer[$k1] = $v1;
			}

			// done!
			return $answer;
		}

	 */
}