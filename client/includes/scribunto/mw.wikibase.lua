--[[
	Registers and defines functions to access Wikibase through the Scribunto extension
	Provides Lua setupInterface

	@since 0.4

	@licence GNU GPL v2+
	@author Jens Ohlig < jens.ohlig@wikimedia.de >
	@author Marius Hoch < hoo@online.de >
]]

local wikibase = {}
-- Caching variable for the wikibase.entity object belonging to the current page
local entity = false

function wikibase.setupInterface()
	local php = mw_interface
	mw_interface = nil


	local getEntityObject = function( id )
		if id == nil then
			return nil
		end

		local entity = php.getEntity( id )
		if type( entity ) ~= 'table' then
			return nil
		end

		return wikibase.entity.create( entity )
	end

	-- @DEPRECATED, uses a legacy plain Lua table holding the entity
	wikibase.getEntity = function()
		local id = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )

		if id == nil then
			return nil
		end

		return php.getEntity( id, true )
	end

	-- Get the mw.wikibase.entity object for the current page
	wikibase.getEntityObject = function()
		if entity ~= false then
			return entity
		end

		local id = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )

		if id == nil then
			entity = nil
		else
			entity = getEntityObject( id )
		end

		return entity
	end

	wikibase.label = function( id )
		local entity = getEntityObject( id )

		if entity == nil then
			return nil
		end

		return entity:getLabel()
	end

	wikibase.sitelink = function( id )
		local entity = getEntityObject( id )

		if entity == nil then
			return nil
		end

		return entity:getSitelink()
	end

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
