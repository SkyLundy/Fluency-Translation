<?php namespace ProcessWire;

/**
 * Returns the localized strings for the translation tool
 */
return function(): object {
  return (object) [
    'name' => __('Translation'),
    'title' => __('Fluency Translation Tool'),
    'description' => __('Translate your text from any language, to any language.'),
    'unconfiguredErrorTitle' => __("Sorry, the translation tool is not ready yet."),
    'unconfiguredErroDescription' => __("Please configure Fluency in the module settings to get started."),
    'fieldLabelFrom' => __('Translate from:'),
    'fieldLabelTo' => __('Translate to:'),
    'fieldLabelYourText' => __('Your Text:'),
    'fieldLabelTranslatedText' => __('Translated Text:'),
    'buttonTranslate' => __('Translate'),
    'serviceUsageTitle' => __('Translation Service Usage Information'),
    'serviceUsageDescription' => __('Click the Refresh button to get translation service usage information'),
    'usageTableCharacterTitle' => __('Character Limit'),
    'usageTableTranslatedTitle' => __('Characters Translated'),
    'usageTableAvailableTitle' => __('Characters Available'),
    'usageTableTotalTitle' => __('Total Usage')
  ];
};

