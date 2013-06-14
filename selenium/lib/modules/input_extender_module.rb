# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for input extender page object

module InputExtenderPage
  include PageObject
  # input extender UI elements
  div(:inputExtender, :class => "ui-inputextender-extension")
  div(:inputExtenderClose, :class => "ui-inputextender-extension-close")
  div(:inputPreview, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]")
  div(:inputPreviewLabel, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]/div[contains(@class, 'valueview-preview-label')]")
  div(:inputPreviewValue, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]/div[contains(@class, 'valueview-preview-value')]")
  #link(:coordinateInputExtenderAdvanced, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/a[contains(@class, 'valueview-expert-globecoordinateinput-advancedtoggler')]")
  #div(:coordinatePrecision, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]")
  #link(:coordinatePrecisionRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-auto')]")
  #link(:coordinatePrecisionRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-prev')]")
  #link(:coordinatePrecisionRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-next')]")
  #link(:coordinatePrecisionRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-curr')]")
  #unordered_list(:coordinatePrecisionMenu, :class => "ui-listrotator-menu", :index => 0)
end
