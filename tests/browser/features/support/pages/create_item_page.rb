# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateItem special page

class CreateItemPage
  include PageObject
  include CreateEntityPage
  include URL

  page_url URL.repo_url('Special:NewItem')
end
