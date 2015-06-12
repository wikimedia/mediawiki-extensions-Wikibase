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
	local infobox = mw.wikibase.capiunto.create( {
		referenceRenderer = function() end
	} )
	infobox:addStatement( property )
	infobox:addRow( 'Missed', 'No statement found' )
	return infobox.args.rows[1]
end

local function testDefaultReferencesRenderer( property )
	local infobox = mw.wikibase.capiunto.create()
	infobox:addStatement( property )
	return infobox.args.rows[1]
end

local function customRenderer( snaks )
	local value = ''

	for k, snak in pairs( snaks ) do
		value = value .. k
	end

	return value
end

local function testCustomReferencesRenderer( property )
	local infobox = mw.wikibase.capiunto.create( {
		referenceRenderer = customRenderer
	} )
	infobox:addStatement( property )
	return infobox.args.rows[1]
end

local tests = {
	-- General
	{ name = 'mw.wikibase.capiunto.create creates table', func = testCreate,
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.capiunto.create has addStatement', func = testCreate_addStatements,
	  expect = { 'function' }
	},

	-- Mainsnak
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

	-- References
	{ name = 'mw.wikibase.capiunto.addStatement with default renderer', func = testDefaultReferencesRenderer,
	  args = { 'LuaTestStringProperty' },
	  expect = { { data = 'Lua :)<ref name="825c5534cc615861fbcccd26828cbc9b5055ab9d">A reference</ref>', label = 'LuaTestStringProperty' } }
	},
	{ name = 'mw.wikibase.capiunto.addStatement with custom renderer', func = testCustomReferencesRenderer,
	  args = { 'LuaTestStringProperty' },
	  expect = { { data = 'Lua :)<ref name="825c5534cc615861fbcccd26828cbc9b5055ab9d">P342</ref>', label = 'LuaTestStringProperty' } }
	},
}

return testframework.getTestProvider( tests )
