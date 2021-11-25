<?php namespace ProcessWire;

/**
 * This returns an object containing translations for client rendered UI elements
 * @param Fluency $fluency Fluency module object
 */
return function(Fluency $fluency): object {
  $sourceLangTitle = $fluency->getConfiguredLanguageData()
                             ->source
                             ->processWire
                             ->title;

  return (object) [
    'ariaSelectedLanguageLabel' => sprintf(__('Translate from %s'), $sourceLangTitle),
    'ariaSelectedLanguageLabel' => __('Selected Language'),
    'translationNotAvailable' => __('Translation not available for this langauge'),
    'languageNotAvailable' => __('Translation not available for this language'),
    'pageNameTranslationNotAvailable' => __('Translation not available for page names in this language')
  ];
};
