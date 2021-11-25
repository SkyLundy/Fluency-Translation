<?php namespace ProcessWire;

/**
 * A utility class that provides helper methods for common needs
 */
class FluencyLocalization {

  /**
   * Holds an instance of the DeepL translator class
   * @var DeepL
   */
  private $deepL;

  /**
   * Holds string for localization directory location
   * @var string
   */
  private $localizationDir;

  public function __construct($deepL) {
    $this->deepL = $deepL;
    $this->localizationDir = __DIR__ . "/../localization";
    $this->cacheDir = "{$this->localizationDir}/cache";
  }

  /**
   * Associates contexts with filenames
   * @var array
   */
  $fileContextAssociations = [
    'pageEditor' => 'page_editor.json',
    'translationTool' => 'translation_tool.json',
    'languageSelectElement' => 'langauge_select_element.json'
  ];

  /**
   * This gets auto-translated module strings for the current language.
   * If the current langauge does not exist, falls back to native English
   * @param  string $context  The purpose of this text, associated with file
   * @return object           k/v translation associations
   */
  public function get(string $context): object {
    $userLanguage = wire('user')->language;


    return (object) $translatedStrings;
  }

  /**
   * This translates and caches all of the module strings to all languages configured with Fluency
   * The default language is required.
   * @return array Array of languages that were successfully translated and cached
   */
  public function localizeConfiguredLanguages(): array {

  }

  /**
   * Gets default text from file
   * @param  string $context Language context for file association
   * @return object
   */
  private function getFromDefault(string $context): object {

  }

  /**
   * This gets translated text from cache
   * @param  string $pwLanguageId ProcessWire Language ID
   * @param  string $context      Language context for file association
   * @return object               Object if file exists, null if it doesn't
   */
  private function getFromCache(string $languageId, $context): ?object {

  }

  /**
   * This writes translations to a cached JSON file
   * @param  string $languageId     ProcessWire language ID
   * @param  object $translatedText Object with translations. Matches structure of default files
   * @return [type]                 [description]
   */
  private function writeToCache(string $languageId, string $context, object $translatedText): bool {

  }
}
