<?php

namespace Wikibase;
use Content, Html, Linker, Skin;

/**
 * Handles the edit action for Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EditEntityAction extends ViewEntityAction {

	/**
	 * @see Action::getName()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * @see FormlessAction::show
	 *
	 * @since 0.1
	 */
	public function show() {
		$req = $this->getRequest();

		if ( $req->getCheck( 'undo' ) ) {
			$latestRevId = $this->getTitle()->getLatestRevID();

			if ( $latestRevId !== 0 ) {
				$latestRevision = \Revision::newFromId( $latestRevId );

				$olderRevision = \Revision::newFromId( $req->getInt( 'undo' ) );
				$newerRevision = \Revision::newFromId( $req->getInt( 'undoafter' ) );

				if ( !is_null( $latestRevision ) && !is_null( $olderRevision ) && !is_null( $newerRevision ) ) {
					/**
					 * @var EntityContent $latestContent
					 * @var EntityContent $olderContent
					 * @var EntityContent $newerContent
					 */
					$olderContent = $olderRevision->getContent();
					$newerContent = $newerRevision->getContent();
					$latestContent = $latestRevision->getContent();


					// TODO: set title and stuffs
					// TODO: add summary and submit things
                    $langCode = $this->getContext()->getLanguage()->getCode();
                    list( $labelCode, $labelText, $labelLang) =
                        Utils::lookupUserMultilangText(
                            $latestContent->getEntity()->getLabels(),
                            Utils::languageChain( $langCode ),
                            array( $langCode, $this->getPageTitle(), $this->getContext()->getLanguage() )
                        );
                    $this->getOutput()->setContext( $this->getContext() );
                    $this->getOutput()->setPageTitle(
                        $this->msg(
                            'difference-title',
                            $labelText
                        )
                    );
                    $diff = $olderContent->getEntity()->getDiff( $newerContent->getEntity() );

                    $diff = $diff->getApplicableDiff( $latestContent->getEntity()->toArray() );

					$this->displayUndoDiff( $diff );
                    $this->showStandardInputs();
				}
			}
		}
		else {
			parent::show();
		}
	}
    /**
     * @return string
     */
    public function getCancelLink() {
        $cancelParams = array();

        return Linker::linkKnown(
            $this->getContext()->getTitle(),
            wfMsgExt( 'cancel', array( 'parseinline' ) ),
            array( 'id' => 'mw-editform-cancel' ),
            $cancelParams
        );
    }

    /**
     * Add style sheets and supporting JS for diff display.
     *
     * @since 0.1
     *
     */
    function showDiffStyle() {
        $this->getOutput()->addModuleStyles( 'mediawiki.action.history.diff' );
    }
    /**
     * Standard summary input and label (wgSummary), abstracted so EditPage
     * subclasses may reorganize the form.
     * Note that you do not need to worry about the label's for=, it will be
     * inferred by the id given to the input. You can remove them both by
     * passing array( 'id' => false ) to $userInputAttrs.
     *
     * @param $summary string The value of the summary input
     * @param $labelText string The html to place inside the label
     * @param $inputAttrs array of attrs to use on the input
     * @param $spanLabelAttrs array of attrs to use on the span inside the label
     *
     * @return array An array in the format array( $label, $input )
     */
    function getSummaryInput( $summary = "", $labelText = null, $inputAttrs = null, $spanLabelAttrs = null ) {
        // Note: the maxlength is overriden in JS to 255 and to make it use UTF-8 bytes, not characters.
        $inputAttrs = ( is_array( $inputAttrs ) ? $inputAttrs : array() ) + array(
            'id' => 'wpSummary',
            'maxlength' => '200',
            'tabindex' => '1',
            'size' => 60,
            'spellcheck' => 'true',
        ) + Linker::tooltipAndAccesskeyAttribs( 'summary' );

        $spanLabelAttrs = ( is_array( $spanLabelAttrs ) ? $spanLabelAttrs : array() ) + array(
            'class' => 'mw-summary',
            'id' => "wpSummaryLabel"
        );

        $label = null;
        if ( $labelText ) {
            $label = Html::element( 'label', $inputAttrs['id'] ? array( 'for' => $inputAttrs['id'] ) : null, $labelText );
            $label = Html::element( 'span', $spanLabelAttrs, $label );
        }

        $input = Html::input( 'wpSummary', $summary, 'text', $inputAttrs );

        return array( $label, $input );
    }
	/**
	 * Displays the undo diff.
	 *
	 * @since 0.1
	 *
	 * @param EntityDiff $diff
	 */
	protected function displayUndoDiff( EntityDiff $diff ) {
		$diffView = $diff->getView();
        $diffView->setContext( $this->getContext() );
		$this->getOutput()->addHTML( Html::rawElement( 'table', array(),  '<colgroup><col class="diff-marker"> <col class="diff-content"><col class="diff-marker"> <col class="diff-content"></colgroup><tbody>' . $diffView->getHtml() . '</tbody>') );
        $this->showDiffStyle();
	}
    /**
     * Returns an array of html code of the following buttons:
     * save, diff, preview and live
     *
     * @param $tabindex int Current tabindex
     *
     * @return array
     */
    public function getEditButtons( &$tabindex ) {
        $buttons = array();

        $temp = array(
            'id'        => 'wpSave',
            'name'      => 'wpSave',
            'type'      => 'submit',
            'tabindex'  => ++$tabindex,
            'value'     => wfMsg( 'savearticle' ),
            'accesskey' => wfMsg( 'accesskey-save' ),
            'title'     => wfMsg( 'tooltip-save' ) . ' [' . wfMsg( 'accesskey-save' ) . ']',
        );
        $buttons['save'] = Html::element( 'input', $temp, '' );

        ++$tabindex; // use the same for preview and live preview
        return $buttons;
    }
    protected function showStandardInputs ( &$tabindex = 2 ) {
        $this->getOutput()->addHTML( Html::openElement( 'form', array( 'id' =>"undo", 'name' => "undo",
            'method' => 'post',
            'action' => $this->getContext()->getTitle()->getLocalURL( array( 'action' => "edit" ) ),
            'enctype' => 'multipart/form-data' ) ) );
        $this->getOutput()->addHTML( "<div class='editOptions'>\n" );


        list( $label, $field ) = $this->getSummaryInput( false, '' );
        $this->getOutput()->addHTML( $label . " " . $field );
        $this->getOutput()->addHTML( "<div class='editButtons'>\n" );
        $this->getOutput()->addHTML( implode( $this->getEditButtons( $tabindex ), "\n" ) . "\n" );

        $cancel = $this->getCancelLink();
        if ( $cancel !== '' ) {
            $cancel .= wfMsgExt( 'pipe-separator' , 'escapenoentities' );
        }
        $edithelpurl = Skin::makeInternalOrExternalUrl( wfMsgForContent( 'wikibase-undo-helppage' ) );
        $edithelp = '<a target="helpwindow" href="' . $edithelpurl . '">' .
            htmlspecialchars( wfMsg( 'edithelp' ) ) . '</a> ' .
            htmlspecialchars( wfMsg( 'newwindow' ) );
        $this->getOutput()->addHTML( "	<span class='editHelp'>{$cancel}{$edithelp}</span>\n" );
        $this->getOutput()->addHTML( "</div><!-- editButtons -->\n</div><!-- editOptions -->\n" );

    }
}
