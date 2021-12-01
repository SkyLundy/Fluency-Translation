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
   * Holds localization directory path
   * @var string
   */
  private $localizationDir;

  /**
   * Holds cache directory path
   * @var string
   */
  private $cacheDir;

  /**
   * Holds source directory path
   * @var string
   */
  private $sourceDir;

  /**
   * Holds an instance of Fluency
   * @var [type]
   */
  private $fluencyModule;

  /**
   * Holds configured languages with ProcessWire associations
   * @var array
   */
  private $configuredLanguageData;

  public function __construct() {
    $this->setVars();
  }

  /**
   * Associates contexts with filenames
   * @var array
   */
  private $fileContexts = [
    'pageEditor' => 'page_editor.json',
    'translationTool' => 'translation_tool.json',
    'languageSelectElement' => 'language_select_element.json'
  ];

  /**
   * Sets instance variables
   * @return void
   */
  private function setVars(): void {
    $fluencyModule = wire('modules')->get('Fluency');
    $fluencyDir = wire('config')->paths->get($fluencyModule);

    // Module
    $this->fluencyModule = $fluencyModule;

    // Paths
    $this->localizationDir = "{$fluencyDir}localization";
    $this->sourceDir = "{$this->localizationDir}/source";
    $this->cacheDir = "{$this->localizationDir}/cache";

    // Language Data
    $this->configuredLanguageData = $fluencyModule->getConfiguredLanguageData();
    $this->sourceLanguage = $this->configuredLanguageData->source;
    $this->targetLanguages = $this->configuredLanguageData->target;
  }

  ///////////////////////
  // Public Interfaces //
  ///////////////////////

  /**
   * This gets auto-translated module strings for the current language.
   * If the current langauge does not exist, falls back to native English
   * @param  string $context  The purpose of this text, associated with file
   * @return object           k/v translation associations
   */
  public function get(string $context): object {
    $userLanguage = wire('user')->language->id;

    // If the user language is the default, get the default language

    return (object) [];
  }

  /**
   * This translates and caches all of the module strings to all languages configured with Fluency
   *
   * This works specifically with the target languages structure in
   * the Fluency::getConfiguredLangaugeData() method
   *
   * @return array Array of language IDs that were successfully translated and cached
   */
  public function localizeModule(): object {
    $errors = [];
    $result = null;

    foreach ($this->targetLanguages as $language) {
      $languageIsoCode = $language->translator->language;
      $processWireId = $language->processWire->id;

      $this->localizeForLanguage($languageIsoCode, $processWireId);
    }

header('Content-Type: application/json');
    echo json_encode($configuredLanguages); die;


    var_dump($errors); die;
  }

  /**
   * Clears all cached translations
   * @return array  Array of language IDs cleared
   */
  public function clearLocalizations(): object {
    return (object) ['ids' => $this->clearCache()];
  }

  /////////////////////////
  // Private ops methods //
  /////////////////////////

  /**
   * Localizes Fluency for a specified langauge
   * @param  string $targetLanguage ISO code for langauge to translate to
   * @return bool                   Success/fail
   */
  private function localizeForLanguage(string $languageIsoCode, $processWireId): bool {

    foreach (array_keys($this->fileContexts) as $context) {
      $defaultStringData = $this->getDefaultStrings($context);
      $translatedStrings = $this->translateStrings($defaultStringData, $languageIsoCode);
      $cacheResult = $this->writeToCache($processWireId, $context, $translatedStrings);
    }

    return true;
  }

  /**
   * Gets default text from file
   * @param  string $context Language context for file association
   * @return object
   */
  private function getDefaultStrings(string $context): array {
    $file = "{$this->sourceDir}/{$this->fileContexts[$context]}";

    // Get file contents
    $fileContents = file_get_contents($file);
    $defaultStrings = (array) json_decode($fileContents);

    // Replace any instances of %{DEFAULT_LANGUAGE} placeholder with name of source langauge
    array_walk($defaultStrings, function(&$v, $k) {
      $v = str_replace('%{DEFAULT_LANGUAGE}', $this->sourceLanguage->translator->name, $v);
    });

    return $defaultStrings;
  }

  /**
   * Translates an array of strings
   * @param  array  $stringData         Strings to translate with keys
   * @param  array  $targetLanguageIson ISO code for target language
   * @return
   */
  private function translateStrings(array $stringData, string $targetLanguageIso): array {
    // Get keys, values as indexed array to work with separately
    $stringKeys = array_keys($stringData);
    $stringVals = array_values($stringData);

    // Translate array of strings
    $translationResult = $this->fluencyModule->translate(
      $this->sourceLanguage->translator->language,
      $stringVals,
      $targetLanguageIso
    );

    // Convert value of each index to translated text
    $translations = array_map(function($translation) {
      return $translation->text;
    }, $translationResult->data->translations);

    // Re-combine translated texts as values for keys
    $translatedStringData = array_combine($stringKeys, $translations);

    return $translatedStringData;
  }

  /**
   * This writes translations to a cached JSON file
   * @param  string $langId         ProcessWire language ID
   * @param  array $translatedTexts Array with translations. Matches structure of default files
   * @return bool
   */
  private function writeToCache(string $langId, string $context, array $translatedText) {
    $filename = "{$this->cacheDir}/{$langId}_{$this->fileContexts[$context]}";
    $content = json_encode($translatedText);

    wire('files')->filePutContents($filename, $content);

  }

  /**
   * This gets translated text from cache
   * @param  string $pwlangId ProcessWire Language ID
   * @param  string $context      Language context for file association
   * @return object               Object if file exists, null if it doesn't
   */
  private function getFromCache(string $langId, $context): ?object {

  }

  /**
   * Clears all cached localization files
   * @param  string $languageId Optional ProcessWire ID for language to clear. Default clears all
   * @return array              Array of ProcessWire language IDs that were cleared
   */
  private function clearCache($languageId = null): array {
    $files = wire('files');
    $cachedFiles = $files->find($this->cacheDir);
    $idsCleared = [];

    foreach ($cachedFiles as $cachedFile) {
      $fileSegments = explode('/', $cachedFile);
      $fileName = end($fileSegments);

      // If there was a language ID specified to clear, and this ain't it, skip
      if ($languageId && strpos($fileName, (string) $languageId) === false) {
        continue;
      }

      $idsCleared[] = explode('_', $fileName)[0];

      $files->unlink($cachedFile, false, true);
    }

    return array_unique($idsCleared);
  }
}
