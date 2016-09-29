--[[
	Integration tests for the mw.wikibase module

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local testframework = require 'Module:TestFramework'

-- Integration tests

local function testGetEntityType()
	return type( mw.wikibase.getEntity() )
end

local function testGetEntitySchemaVersion()
	return mw.wikibase.getEntity().schemaVersion
end

local function testGetEntityObjectType()
	return type( mw.wikibase.getEntityObject() )
end

local function testGetEntityObjectIsCloned()
	mw.wikibase.getEntityObject( 'Q199024' ).id = 'a'

	-- We should get a freshly cloned table here, so the changes above wont persist
	return mw.wikibase.getEntityObject( 'Q199024' ).id
end

local function testGetEntityObjectSchemaVersion()
	return mw.wikibase.getEntityObject().schemaVersion
end

local function testGetEntityObjectForeignLabel()
	return mw.wikibase.getEntityObject( 'Q199024' ):getLabel( 'de' )
end

local function testRenderSnak()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	local snak = entity['claims']['P342'][1]['qualifiers']['P342'][1]

	return mw.wikibase.renderSnak( snak )
end

local function testRenderSnaks()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	local snaks = entity['claims']['P342'][1]['qualifiers']

	return mw.wikibase.renderSnaks( snaks )
end

local tests = {
	-- Integration tests

	{ name = 'mw.wikibase.getEntityIdForCurrentPage', func = mw.wikibase.getEntityIdForCurrentPage,
	  expect = { 'Q32487' }
	},
	{ name = 'mw.wikibase.getEntity (type)', func = testGetEntityType, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.getEntity (schema version)', func = testGetEntitySchemaVersion,
	  expect = { 2 }
	},
	{ name = 'mw.wikibase.getEntityObject (type)', func = testGetEntityObjectType, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.getEntityObject (is cloned)', func = testGetEntityObjectIsCloned, type='ToString',
	  expect = { 'Q199024' }
	},
	{ name = 'mw.wikibase.getEntityObject (schema version)', func = testGetEntityObjectSchemaVersion,
	  expect = { 2 }
	},
	{ name = "mw.wikibase.getEntityObject (foreign access - doesn't exist)", func = mw.wikibase.getEntityObject,
	  args = { 'Q1223214234' },
	  expect = { nil }
	},
	{ name = "mw.wikibase.getEntityObject (foreign access)", func = testGetEntityObjectForeignLabel,
	  expect = { 'Arbitrary access \\o/' }
	},
	{ name = 'mw.wikibase.getEntityObject (id must be string)', func = mw.wikibase.getEntityObject,
	  args = { 123 },
	  expect = "bad argument #1 to 'getEntity' (string or nil expected, got number)"
	},
	{ name = 'mw.wikibase.label', func = mw.wikibase.label, type='ToString',
	  args = { 'Q32487' },
	  expect = { 'Lua Test Item' }
	},
	{ name = 'mw.wikibase.label (no such item)', func = mw.wikibase.label, type='ToString',
	  args = { 'Q1224342342' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.label (connected item)', func = mw.wikibase.label, type='ToString',
	  args = {},
	  expect = { 'Lua Test Item' }
	},
	{ name = 'mw.wikibase.label (no label)', func = mw.wikibase.label, type='ToString',
	  args = { 'Q32488' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.getLabelWithLang', func = mw.wikibase.getLabelWithLang, type='ToString',
	  args = { 'Q32487' },
	  expect = { 'Lua Test Item', 'de' }
	},
	{ name = 'mw.wikibase.getLabelWithLang (no such item)', func = mw.wikibase.getLabelWithLang, type='ToString',
	  args = { 'Q1224342342' },
	  expect = { nil, nil }
	},
	{ name = 'mw.wikibase.getLabelWithLang (connected item)', func = mw.wikibase.getLabelWithLang, type='ToString',
	  args = {},
	  expect = { 'Lua Test Item', 'de' }
	},
	{ name = 'mw.wikibase.getLabelWithLang (no label)', func = mw.wikibase.getLabelWithLang, type='ToString',
	  args = { 'Q32488' },
	  expect = { nil, nil }
	},
	{ name = 'mw.wikibase.description', func = mw.wikibase.description, type='ToString',
	  args = { 'Q32487' },
	  expect = { 'Description of Q32487' }
	},
	{ name = 'mw.wikibase.description (connected item)', func = mw.wikibase.description, type='ToString',
	  args = {},
	  expect = { 'Description of Q32487' }
	},
	{ name = 'mw.wikibase.description (no such item)', func = mw.wikibase.description, type='ToString',
	  args = { 'Q1224342342' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.description (no such description)', func = mw.wikibase.description, type='ToString',
	  args = { 'P342' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.getDescriptionWithLang (connected item)', func = mw.wikibase.getDescriptionWithLang, type='ToString',
	  args = {},
	  expect = { 'Description of Q32487', 'de' }
	},
	{ name = 'mw.wikibase.getDescriptionWithLang (no such item)', func = mw.wikibase.getDescriptionWithLang, type='ToString',
	  args = { 'Q1224342342' },
	  expect = { nil, nil }
	},
	{ name = 'mw.wikibase.getDescriptionWithLang (no such description)', func = mw.wikibase.getDescriptionWithLang, type='ToString',
	  args = { 'P342' },
	  expect = { nil, nil }
	},
	{ name = 'mw.wikibase.sitelink', func = mw.wikibase.sitelink, type='ToString',
	  args = { 'Q32487' },
	  expect = { 'WikibaseClientDataAccessTest' }
	},
	{ name = 'mw.wikibase.sitelink (connected item)', func = mw.wikibase.sitelink, type='ToString',
	  args = {},
	  expect = "bad argument #1 to 'sitelink' (string expected, got nil)"
	},
	{ name = 'mw.wikibase.sitelink', func = mw.wikibase.sitelink, type='ToString',
	  args = { 'Q32488' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.renderSnak', func = testRenderSnak, type='ToString',
	  expect = { 'A qualifier Snak' }
	},
	{ name = 'mw.wikibase.renderSnak (must be table)', func = mw.wikibase.renderSnak,
	  args = { 'meep' },
	  expect = "bad argument #1 to 'renderSnak' (table expected, got string)"
	},
	{ name = 'mw.wikibase.renderSnaks', func = testRenderSnaks, type='ToString',
	  expect = { 'A qualifier Snak, Moar qualifiers' }
	},
	{ name = 'mw.wikibase.renderSnaks (must be table)', func = mw.wikibase.renderSnaks,
	  args = { 'meep' },
	  expect = "bad argument #1 to 'renderSnaks' (table expected, got string)"
	},
	{ name = 'mw.wikibase.resolvePropertyId', func = mw.wikibase.resolvePropertyId,
	  args = { 'LuaTestStringProperty' },
	  expect = { 'P342' }
	},
	{ name = 'mw.wikibase.resolvePropertyId (property id passed)', func = mw.wikibase.resolvePropertyId,
	  args = { 'P342' },
	  expect = { 'P342' }
	},
	{ name = 'mw.wikibase.resolvePropertyId (label not found)', func = mw.wikibase.resolvePropertyId,
	  args = { 'foo' },
	  expect = { nil }
	},
}

return testframework.getTestProvider( tests )
