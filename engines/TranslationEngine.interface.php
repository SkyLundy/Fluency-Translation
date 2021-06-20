<?php namespace ProcessWire;

interface TranslationEngine {

  /**
   * Returns a standardized format of information for usage by Fluency. The
   * return object should contain the following properties
   *
   * (object) [
   *   'name' => 'Engine Name', // Human readable name of engine
   *   'engineFilename' => 'Engine.class.php', // name of the file where engine is located
   *   'apiVersion' => 3, // Version of API in use by this engine int|float
   * ]
   *
   * @return object Object with engine information
   */
  static function getEngineInfo(): object;

  /**
   * Makes a request to check that the API key provided is valid/active
   * @return bool
   */
  function apiKeyIsValid(): bool;

  /**
   * Public interface for translating content. Relays requests to the engine's
   * API class
   * @return object Object with translation results
   */
  function translate(
    string $sourceLanguage,
    $content, // May be string|array
    string $targetLanguage,
    array $options
  ): object;

  /**
   * Should object containing sourceand target properties each with arrays.
   * @return object
   */
  function getLanguages(): object;

  /**
   * Should return API usage data if translation service supports it, null if
   * it doesn't
   * @return object Object containing usage
   */
  function getApiUsage(): object;

  /**
   * This should build the fields that will be used by Fluency.config.php
   * Each translation engine will build their own configuration fields so that
   * the proper information is stored for each engine usage.
   * @return  InputField object containing all configuration fields
   */
  // function moduleConfigFields($fields);
}
