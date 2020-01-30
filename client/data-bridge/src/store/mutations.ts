import EditDecision from '@/definitions/EditDecision';
import clone from '@/store/clone';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
	TARGET_LABEL_SET,
	ORIGINAL_STATEMENT_SET,
	APPLICATION_ERRORS_ADD,
	EDITDECISION_SET,
	ENTITY_TITLE_SET,
	ORIGINAL_HREF_SET,
	PAGE_TITLE_SET,
} from '@/store/mutationTypes';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';
import Statement from '@/datamodel/Statement';
import ApplicationError from '@/definitions/ApplicationError';
import { Mutations } from 'vuex-smart-module';
import Application from '@/store/Application';

export class RootMutations extends Mutations<Application> {

	public [ PROPERTY_TARGET_SET ]( targetProperty: string ): void {
		this.state.targetProperty = targetProperty;
	}

	public [ EDITFLOW_SET ]( editFlow: string ): void {
		this.state.editFlow = editFlow;
	}

	public [ APPLICATION_STATUS_SET ]( status: ValidApplicationStatus ): void {
		this.state.applicationStatus = status;
	}

	public [ TARGET_LABEL_SET ]( label: Term ): void {
		this.state.targetLabel = label;
	}

	public [ ORIGINAL_STATEMENT_SET ]( revision: Statement ): void {
		this.state.originalStatement = clone( revision );
	}

	public [ APPLICATION_ERRORS_ADD ]( errors: ApplicationError[] ): void {
		this.state.applicationErrors.push( ...errors );
	}

	public [ EDITDECISION_SET ]( editDecision: EditDecision ): void {
		this.state.editDecision = editDecision;
	}

	public [ ENTITY_TITLE_SET ]( entityTitle: string ): void {
		this.state.entityTitle = entityTitle;
	}

	public [ PAGE_TITLE_SET ]( pageTitle: string ): void {
		this.state.pageTitle = pageTitle;
	}

	public [ ORIGINAL_HREF_SET ]( orginalHref: string ): void {
		this.state.originalHref = orginalHref;
	}
}
