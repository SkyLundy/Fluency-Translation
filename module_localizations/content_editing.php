<?php namespace ProcessWire;

/**
 * This returns an object containing translations for client rendered UI elements
 * @param Fluency $fluency Fluency module object
 */
return function(Fluency $fluency): object {
  $configuredLangs = $fluency->getConfiguredLanguageData();

  return (object) [
    'translatedTrigger' => sprintf(__('Translate from %s'), $configuredLangs->source->title),
    'translationAvailable' => __('Translation Service Available'),
    'translationNotAvailable' => __('Translation not available for this langauge'),
    'languageNotAvailable' => __('Translation not available for this language'),
    'pageNameTranslationNotAvailable' => __('Translation not available for page names in this language'),
  ];
};
