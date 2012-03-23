<?php
/**
 * Timeline List-Module for the MediaWiki web API. Invoke with list=timeline
 */

class ApiQueryWikidataProp extends ApiQueryBase {


    public function __construct( $query, $moduleName, $prefix = 'wd' ) {
        parent :: __construct( $query, $moduleName, $prefix );
    }

    public function execute() {
        $params = $this->extractRequestParams();

        $titles = $this->getPageSet()->getTitles();

        foreach ( $titles as $t ) {
            $path = array( 'query', 'pages', $t->getArticleID(), $this->getModuleName() );

            $this->addWikidataForPage( $t, $path, $params );
        }
    }

    public function addWikidataForPage( Title $title, $path, $params ) {
        $props = $params['prop'];
        $langs = $params['lang'];

        $result = $this->getResult();
        #FIXME: handle indexed items correctly, especially multiple statuements for a single property, or multiple aliases for a single language!

        $page = WikiPage::factory( $title ); # XXX: use a cached copy?
        $content = $page->getContent();
        $data =  array();

        if ( empty($props) or in_array('*', $props) ) $props = $content->getPropertyNames();
        if ( empty($lang) or in_array('*', $lang) ) $lang = null;

        foreach ( $props as $p ) {
            $d = $content->getPropertyMultilang($p, $lang);

            if ($d) $data[$p] = $d;
        }

        $result->addValue( $path, null, $data );
    }

    public function getDescription() {
        return array ("Includes structured data for each page. ");
    }

    public function getExamples() {
        return array (
            "Get structured data of Data:Example:",
            "  api.php?action=query&prop=wikidata&titles=Data:Example",
        );
    }

    public function getVersion() {
        return __CLASS__ . ': $Id$';
    }

	public function getAllowedParams() {
        $params = array(
            'prop' => array(
                ApiBase::PARAM_DFLT => '*',
                ApiBase::PARAM_TYPE => array_keys( WikiTalkQueryTimeline::$all_properties ),
                ApiBase::PARAM_ISMULTI => true,
            ),
            'prop' => array(
                ApiBase::PARAM_DFLT => '*',
                ApiBase::PARAM_TYPE => array_keys( WikiTalkQueryTimeline::$all_properties ),
                ApiBase::PARAM_ISMULTI => true,
            ),
        );

		return $params;
	}

	public function getParamDescription() {
        $params = array(
            'prop' => array( 'What properties to return from the structured data records',
                            'To get all properties, use "*" (this is the default).'),

            'lang' => array( 'The languages to include for multilingual values',
                            'To get all languages, use "*" (this is the default).')
        );

		return $params;
	}

}
