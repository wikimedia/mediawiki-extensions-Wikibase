--[[
	Unit and integration tests for the mw.wikibase.capiunto module

	@license GNU GPL v2+
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local testframework = require 'Module:TestFramework'

-- @todo mock mw.getCurrentFrame()

local function testCreate()
	return type( mw.wikibase.capiunto.create() )
end

local function testCreate_addStatements()
	return type( mw.wikibase.capiunto.create().addStatement )
end

local function testAddStatement( property )
	local infobox = mw.wikibase.capiunto.create()
	infobox:addStatement( property )
	infobox:addRow( 'Missed', 'No statement found' )
	return infobox.args.rows[1]
end

local tests = {
	{ name = 'mw.wikibase.capiunto.create creates table', func = testCreate,
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.capiunto.create has addStatement', func = testCreate_addStatements,
	  expect = { 'function' }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with label', func = testAddStatement,
	  args = { 'LuaTestStringProperty' },
	  expect = { { data = 'Lua :)', label = 'LuaTestStringProperty' } }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with id', func = testAddStatement,
	  args = { 'P342' },
	  expect = { { data = 'Lua :)', label = 'P342' } }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with unset label', func = testAddStatement,
	  args = { 'LuaTestItemProperty' },
	  expect = { { data = 'No statement found', label = 'Missed' } }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with unset id', func = testAddStatement,
	  args = { 'P456' },
	  expect = { { data = 'No statement found', label = 'Missed' } }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with non-existing label', func = testAddStatement,
	  args = { 'FooBar' },
	  expect = { { data = 'No statement found', label = 'Missed' } }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with non-existing id', func = testAddStatement,
	  args = { 'P123456789' },
	  expect = { { data = 'No statement found', label = 'Missed' } }
	},
}

return testframework.getTestProvider( tests )
