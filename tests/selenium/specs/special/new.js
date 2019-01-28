'use strict';

const assert = require( 'assert' ),
	NewSchemaPage = require( '../../pageobjects/newschema.page' ),
	SchemaPage = require( '../../pageobjects/schema.page' );

describe( 'NewSchema:Page', () => {

	it( 'request with "createpage" right shows form', () => {
		NewSchemaPage.open();

		assert.ok( NewSchemaPage.showsForm() );
	} );

	it( 'shows a submit button', () => {
		NewSchemaPage.open();
		NewSchemaPage.schemaSubmitButton.waitForVisible();
	} );

	it( 'is possible to create a new schema', () => {
		NewSchemaPage.open();
		NewSchemaPage.setLabel( 'Testlabel' );
		NewSchemaPage.setDescription( 'A schema created with selenium browser tests' );
		NewSchemaPage.setAliases( 'Testschema |Schema created by test' );
		NewSchemaPage.setShExC( '<empty> {}' );
		NewSchemaPage.clickSubmit();

		const actualLabel = SchemaPage.getLabel(),
			actualDescription = SchemaPage.getDescription(),
			actualAliases = SchemaPage.getAliases(),
			actualShExC = SchemaPage.getShExC(),
			actualNamespace = SchemaPage.getNamespace();
		assert.equal( 'Testlabel', actualLabel );
		assert.equal( 'A schema created with selenium browser tests', actualDescription );
		assert.equal( 'Testschema | Schema created by test', actualAliases );
		assert.equal( '<empty> {}', actualShExC );
		assert.equal( 'Schema', actualNamespace );
	} );

} );
