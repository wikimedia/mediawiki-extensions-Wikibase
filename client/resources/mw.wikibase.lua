wikibase = {}

function wikibase.setupInterface()
  for k, v in pairs( mw_interface ) do
    wikibase[k] = v
  end
  mw = mw or {}
  mw.wikibase = wikibase
  package.loaded['mw.wikibase'] = wikibase
end

return wikibase
