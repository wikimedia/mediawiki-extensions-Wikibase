import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';
import { MediaWiki } from '@/@types/mediawiki/MwWindow';
import ReferenceListChangeCounter from '@/ReferenceListChangeCounter';

export default class StatementTracker {
	private track: MediaWiki['track'];
	private refChangeCounter: ReferenceListChangeCounter;

	public constructor(
		track: MediaWiki['track'],
		refChangeCounter: ReferenceListChangeCounter,
	) {
		this.track = track;
		this.refChangeCounter = refChangeCounter;
	}

	public trackChanges( oldStatement: Statement|null, newStatement: Statement ): void {
		if ( oldStatement === null ) {
			// newly created statement
			return;
		}
		const referenceChangeCount = this.refChangeCounter.countOldReferencesRemovedOrChanged(
			oldStatement.getReferences(),
			newStatement.getReferences(),
		);
		const oldRefCount = oldStatement.getReferences().length;
		const newRefCount = newStatement.getReferences().length;
		if ( this.mainSnakChanged( oldStatement, newStatement ) ) {
			if ( referenceChangeCount === oldRefCount ) {
				// statement value changed + all refs changed
				this.track( 'counter.wikibase.view.tainted-ref.mainSnakChanged.allReferencesChanged', 1 );
			} else if ( referenceChangeCount === 0 && oldRefCount === newRefCount ) {
				// statement value changed + no refs changed
				this.track( 'counter.wikibase.view.tainted-ref.mainSnakChanged.noReferencesChanged', 1 );
			} else {
				// statement value changed + one or more refs changed and not all refs changed
				this.track( 'counter.wikibase.view.tainted-ref.mainSnakChanged.someNotAllReferencesChanged', 1 );
			}
		} else {
			if ( referenceChangeCount >= 1 ) {
				// statement value NOT changed + one or more refs changed
				this.track( 'counter.wikibase.view.tainted-ref.mainSnakUnchanged.someReferencesChanged', 1 );
			}
			if ( this.qualifierChange( oldStatement, newStatement ) ) {
				// statement value NOT changed + one or more qualifiers changed
				this.track( 'counter.wikibase.view.tainted-ref.mainSnakUnchanged.someQualifierChanged', 1 );
			}
		}
	}

	private mainSnakChanged( oldStatement: Statement, newStatement: Statement ): boolean {
		return ( !oldStatement.getClaim().getMainSnak().equals( newStatement.getClaim().getMainSnak() ) );
	}

	private qualifierChange( oldStatement: Statement, newStatement: Statement ): boolean {
		const oldQualifiersList = oldStatement.getClaim().getQualifiers();
		const newQualifiersList = newStatement.getClaim().getQualifiers();

		return !oldQualifiersList.equals( newQualifiersList );
	}
}
