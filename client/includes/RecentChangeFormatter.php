<?php

namespace Wikibase;

class RecentChangeFormatter {

        /**
         * Generates a recent change line
         *
         * @since 0.2
         *
         * @param \ChangesList $cl
	 * @param string $s
	 * @param \RecentChange $rc
         *
         * @return string
         */
        public static function changeLine( &$cl, &$s, $rc ) {
		$repoBase = Settings::get( 'repoBase' );
                $username = $rc->getAttribute( 'rc_user_text' );
		
		$s = '';
                $s .= '(diff | hist) . . ';
                $s .= \Linker::link( \Title::newFromText( $rc->getAttribute( 'rc_title' ) ) );
		$s .= $cl->insertTimestamp( $s, $rc );
		//$s .= "<a href='{$repoBase}" . strtoupper( $change['prefixed_id'] ) . "' class='plainlinks'>";
                //$s .= $change['label'];
                //$s .= ' (' . $change['prefixed_id'] . ') ';
                //$s .= '</a>';
                //$s .= ' . . ';
                //$s .= "<span class='wb-$changeType wb-change'>" . $this->getChangeType( $change['type'] ) . '</span>';

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

		$s .= $userlinks;

                $comment = " (" . $rc->getAttribute( 'rc_comment' ) . ")";

                $s .= $comment;
		$s .= "</li>";
	}
}
