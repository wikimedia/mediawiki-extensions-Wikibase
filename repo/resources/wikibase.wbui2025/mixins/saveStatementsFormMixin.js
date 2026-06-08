const { mapActions } = require( 'pinia' );

const wbui2025 = require( 'wikibase.wbui2025.lib' );

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
						return { success: true };
					} )
					.catch( ( errorObj ) => {
						const errorHtml = errorObj &&
						errorObj.errorData &&
						errorObj.errorData.errors &&
						errorObj.errorData.errors.length > 0 &&
						typeof errorObj.errorData.errors[ 0 ].html === 'string'
							? errorObj.errorData.errors[ 0 ].html
							: null;

						const errorText = errorHtml ? null : wbui2025.util.extractErrorMessage(
							errorObj,
							mw.msg( 'wikibase-publishing-error' )
						);
						this.addStatusMessage( {
							type: 'error',
							attachTo: currentFormRef,
							html: errorHtml,
							text: errorText
						} );
						clearTimeout( progressTimeout );
						this.showProgress = false;
						this.formSubmitted = false;
						return { success: false };
					} );
			}
		}
	) };

module.exports = saveStatementsFormMixin;
