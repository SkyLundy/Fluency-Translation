<?php namespace ProcessWire;

require_once 'classes/DeepL.class.php';

class FluencyLocalization {

  /**
   * Get translation trigger text in English
   * @param  string $fromLang Language that translations are from
   * @param  array $toLangs   Array of languages that are available for translation
   * @return array            Array of translation trigger strings
   */
  private function englishTriggers(string $fromLang, string $toLang): array {
    $phraseBase = "Translate %{TO_LANG} from {$FROM_LANG}";

    $phrases = [
      'default' => 'Translate all from {$FROM_LANG}',
      'unavailable' => "Translation service not available for this language"
    ];

    $langs = [
      'DE' => 'German',
      'EN' => 'English',
      'ES' => 'Spanish',
      'FR' => 'French',
      'IT' => 'Italian',
      'JA' => 'Japanese',
      'NL' => 'Dutch',
      'PL' => 'Polish',
      'PT' => 'Portuguese',
      'RU' => 'Russian',
      'ZH' => 'Chinese'
    ];

    return $this->translateTriggerText($phraseBase, $phrases, $langs, $toLangs);
  }

  /**
   * Public method called to create translate trigger link text in CMS UI
   * @param  string $inLang   Language that the text should be in
   * @param  string $fromLang Source translation language
   * @param  string $toLangs  Languages able to be translated
   * @return array            Array of values
   */
  public function getTranslationTriggerText(
    string $inLang,
    string $fromLang,
    array $toLangs
  ): array {

  }


}
