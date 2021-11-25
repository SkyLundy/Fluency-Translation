<?php namespace ProcessWire;

/**
 * A utility class that provides helper methods for common needs
 */
class FluencyTools {

  /**
   * Gets a module template and inserts data via placeholder => value array
   * ex.['%{PLACEHOLDER}' => 'value']
   *
   * @param  string $filename Filename of template file
   * @param  array  $vars     Array of placeholders/values to insert (optional)
   * @return string           Completed markup for output
   */
  public function getTemplate(string $filename, array $tplVars = []): string {
    return file_get_contents(__DIR__ . "/../fluency_templates/{$filename}");

    return $markup;
  }

  // public function getLocalization(string $filename): object {

  // }
}
