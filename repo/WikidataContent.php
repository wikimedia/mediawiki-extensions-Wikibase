<?php
class WikidataContent extends Content {

    /**
     * @return String a string representing the content in a way useful for building a full text search index.
     *         If no useful representation exists, this method returns an empty string.
     */
    public function getTextForSearchIndex()
    {
        // TODO: Implement getTextForSearchIndex() method.
    }

    /**
     * @return String the wikitext to include when another page includes this  content, or false if the content is not
     *         includable in a wikitext page.
     */
    public function getWikitextForTransclusion()
    {
        // TODO: Implement getWikitextForTransclusion() method.
    }

    /**
     * Returns a textual representation of the content suitable for use in edit summaries and log messages.
     *
     * @param int $maxlength maximum length of the summary text
     * @return String the summary text
     */
    public function getTextForSummary($maxlength = 250)
    {
        // TODO: Implement getTextForSummary() method.
    }

    /**
     * Returns native represenation of the data. Interpretation depends on the data model used,
     * as given by getDataModel().
     *
     * @return mixed the native representation of the content. Could be a string, a nested array
     *         structure, an object, a binary blob... anything, really.
     */
    public function getNativeData()
    {
        // TODO: Implement getNativeData() method.
    }

    /**
     * returns the content's nominal size in bogo-bytes.
     *
     * @return int
     */
    public function getSize()
    {
        // TODO: Implement getSize() method.
    }

    /**
     * Returns true if this content is countable as a "real" wiki page, provided
     * that it's also in a countable location (e.g. a current revision in the main namespace).
     *
     * @param $hasLinks Bool: if it is known whether this content contains links, provide this information here,
     *                        to avoid redundant parsing to find out.
     */
    public function isCountable($hasLinks = null)
    {
        // TODO: Implement isCountable() method.
    }

    /**
     * @param null|Title $title
     * @param null $revId
     * @param null|ParserOptions $options
     * @return ParserOutput
     */
    public function getParserOutput(Title $title = null, $revId = null, ParserOptions $options = NULL)
    {
        // TODO: Implement getParserOutput() method.
    }
}