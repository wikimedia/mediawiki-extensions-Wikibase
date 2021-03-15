import ErrorSavingEditConflict from '@/presentation/components/ErrorSavingEditConflict';

export default {
	title: 'ErrorSavingEditConflict',
	component: ErrorSavingEditConflict,
};

export function normal() {
	return {
		components: { ErrorSavingEditConflict },
		template: `<div style="max-width: 550px; max-height: 550px; border: 1px solid black;">
			<ErrorSavingEditConflict />
		</div>`,
	};
}
