import EditDecision from '@/definitions/EditDecision';
import clone from '@/store/clone';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import DataValue from '@/datamodel/DataValue';
import Term from '@/datamodel/Term';
import ApplicationError from '@/definitions/ApplicationError';
import { Mutations } from 'vuex-smart-module';
import Application from '@/store/Application';

export class RootMutations extends Mutations<Application> {

	public setPropertyPointer( targetProperty: string ): void {
		this.state.targetProperty = targetProperty;
	}

	public setEditFlow( editFlow: string ): void {
		this.state.editFlow = editFlow;
	}

	public setApplicationStatus( status: ValidApplicationStatus ): void {
		this.state.applicationStatus = status;
	}

	public setTargetLabel( label: Term ): void {
		this.state.targetLabel = label;
	}

	public addApplicationErrors( errors: ApplicationError[] ): void {
		this.state.applicationErrors.push( ...errors );
	}

	public setEditDecision( editDecision: EditDecision ): void {
		this.state.editDecision = editDecision;
	}

	public setTargetValue( dataValue: DataValue ): void {
		this.state.targetValue = clone( dataValue );
	}

	public setEntityTitle( entityTitle: string ): void {
		this.state.entityTitle = entityTitle;
	}

	public setPageTitle( pageTitle: string ): void {
		this.state.pageTitle = pageTitle;
	}

	public setOriginalHref( orginalHref: string ): void {
		this.state.originalHref = orginalHref;
	}
}
