--[[
	Integration tests verifiying that we cache entities in memory in the right way.

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local testframework = require 'Module:TestFramework'

function testGetEntities()
	-- Get Q1-Q18 and Q199024 (19 entities)
	for i = 1, 18 do
		mw.wikibase.getEntity( 'Q' .. i )
		-- Always load Q2 to keep it on top of the cache
		mw.wikibase.getEntity( 'Q2' )
	end
	mw.wikibase.getEntity( 'Q199024' )

	-- These are in memory cached now (Q3-Q5 have been purged)
	mw.wikibase.getEntity( 'Q2' )
	mw.wikibase.getEntity( 'Q6' )
	mw.wikibase.getEntity( 'Q16' )
	mw.wikibase.getEntity( 'Q12' )
	mw.wikibase.getEntity( 'Q13' )
	mw.wikibase.getEntity( 'Q14' )

	-- Q4 is no longer cached, thus we just loaded exactly 20 entities
	mw.wikibase.getEntity( 'Q4' )

	-- Make sure this points to the right table (cached)
	return mw.wikibase.getEntity( 'Q199024' ).id
end

local tests = {
	{ name = "Load 20 entities and operate on cache then", func = testGetEntities,
	  expect = { 'Q199024' }
	}
}

return testframework.getTestProvider( tests )
