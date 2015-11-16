# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for authority control gadget page

module AuthorityControlGadgetPage
  include PageObject

  def statement_auth_control_link(group_index, claim_index)
    element('a', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementview:nth-child(#{claim_index}) div.wikibase-snakview-value > a.external")
  end
end
