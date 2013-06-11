# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for time page object

module TimePage
  include PageObject
  # time UI elements
  div(:timeInputExtender, :class => "ui-inputextender-extension")
  div(:timeInputExtenderClose, :class => "ui-inputextender-extension-close")
  div(:timePreview, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]")
  div(:timePreviewLabel, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]/div[contains(@class, 'valueview-preview-label')]")
  div(:timePreviewValue, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]/div[contains(@class, 'valueview-preview-value')]")
  link(:timeInputExtenderAdvanced, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/a[contains(@class, 'valueview-expert-timeinput-advancedtoggler')]")
  div(:timePrecision, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]")
  link(:timePrecisionRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-auto')]")
  link(:timePrecisionRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-prev')]")
  link(:timePrecisionRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-next')]")
  link(:timePrecisionRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-curr')]")
  unordered_list(:timePrecisionMenu, :class => "ui-listrotator-menu")
  div(:timeCalendar, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]")
  link(:timeCalendarRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-auto')]")
  link(:timeCalendarRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-prev')]")
  link(:timeCalendarRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-next')]")
  link(:timeCalendarRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-curr')]")
  unordered_list(:timePrecisionMenu, :class => "ui-listrotator-menu", :index => 0)
  unordered_list(:timeCalendarMenu, :class => "ui-listrotator-menu", :index => 1)

  def test_time
    puts "here i am"
  end
end
