<?php

namespace Wikibase;

class ExternalChangesList {

        /**
         * Generates a recent change line
         *
         * @since 0.2
         *
	 * @param \OldChangesList $cl
	 * @param \RecentChange $rc
	 *
         * @return string
         */
	public static function changesLine( &$cl, $rc ) {
		$repoBase = Settings::get( 'repoBase' );
                $username = $rc->getAttribute( 'rc_user_text' );
		
		$line = '';
                $line .= '(diff | hist) . . ';
                $line .= \Linker::link( \Title::newFromText( $rc->getAttribute( 'rc_title' ) ) );
		$line .= $cl->insertTimestamp( $line, $rc );
		//$line .= "<a href='{$repoBase}" . strtoupper( $change['prefixed_id'] ) . "' class='plainlinks'>";
                //$line .= $change['label'];
                //$line .= ' (' . $change['prefixed_id'] . ') ';
                //$line .= '</a>';
                //$line .= ' . . ';
                //$line .= "<span class='wb-$changeType wb-change'>" . $this->getChangeType( $change['type'] ) . '</span>';

		if ( \User::isIP( $username ) ) {
			$userlinks = "<a href='{$repoBase}Special:Contributions/{$username}' class='plainlinks'>$username</a>";
			$userlinks .= " (";
			$userlinks .= "<a href='{$repoBase}User_talk:{$username}' class='plainlinks'>talk</a>";
			$userlinks .= ")";
		} else {
			$userlinks = "<a href='{$repoBase}User:{$username}' class='plainlinks'>$username</a>";
			$userlinks .= " (";
			$userlinks .= "<a href='{$repoBase}User_talk:{$username}' class='plainlinks'>talk</a>";
			$userlinks .= " | ";
			$userlinks .= "<a href='{$repoBase}Special:Contributions/{$username}' class='plainlinks'>contribs</a>";
			$userlinks .= ")";
		}

		$line .= $userlinks;

                $comment = " (" . $rc->getAttribute( 'rc_comment' ) . ")";

                $line .= $comment;
		$line .= "</li>";

		return $line;
	}
}
