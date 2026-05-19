const { mapActions } = require( 'pinia' );

const wbui2025 = require( 'wikibase.wbui2025.lib' );
const ErrorObject = wbui2025.api.ErrorObject;

const saveStatementsFormMixin = {
	methods: Object.assign(
		mapActions( wbui2025.store.useMessageStore, [
			'addStatusMessage'
		] ),
		mapActions( wbui2025.store.useEditStatementsStore, {
			saveChangedStatements: 'saveChangedStatements'
		} ),
		{
			submitFormWithElementRef( currentFormRef ) {
				this.formSubmitted = true;
				const progressTimeout = setTimeout( () => {
					this.showProgress = true;
				}, 300 );
				return this.saveChangedStatements( this.entityId )
					.then( () => {
						this.cancelForm();
						this.addStatusMessage( {
							type: 'success',
							text: mw.msg( 'wikibase-publishing-succeeded' )
						} );
						clearTimeout( progressTimeout );
						this.showProgress = false;
					} )
					.catch( ( errorObj ) => {
						const errorHtml = errorObj instanceof ErrorObject &&
						errorObj.errorMessage instanceof $ ?
							// TODO use $( '<div>' ).append( errorObj.errorMessage ).html() once T304945 is fixed
							errorObj.errorMessage[ 0 ].outerHTML :
							null;
						this.addStatusMessage( {
							type: 'error',
							attachTo: currentFormRef,
							html: errorHtml,
							text: errorHtml ? null : wbui2025.util.extractErrorMessage(
								errorObj,
								mw.msg( 'wikibase-publishing-error' )
							)
						} );

						clearTimeout( progressTimeout );
						this.showProgress = false;
						this.formSubmitted = false;
					} );
			}
		}
	) };

module.exports = saveStatementsFormMixin;
