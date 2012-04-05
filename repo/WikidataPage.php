<?php
/**
 * Created by JetBrains PhpStorm.
 * User: H
 * Date: 05.04.12
 * To change this template use File | Settings | File Templates.
 */

class WikidataPage extends Article {

    public function view() {
        global $wgOut;
        $contentObject = $this->getContentObject();
        $arrayContent = $contentObject->getNativeData();
        $wgOut->addElement( 'pre', array(),print_r( $arrayContent, true ) );
    }

}