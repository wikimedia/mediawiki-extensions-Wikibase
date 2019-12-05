import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';

export default class TaintedChecker {
	public check( oldStatement: Statement, newStatement: Statement ): boolean {
		return !( oldStatement.getClaim().getMainSnak().equals( newStatement.getClaim().getMainSnak() ) ) &&
			oldStatement.getReferences().equals( newStatement.getReferences() ) &&
			!oldStatement.getReferences().isEmpty();
	}
}
