<?php namespace ProcessWire;

/**
 * A utility class that provides helper methods for common needs
 */
class FluencyTools {

  /**
   * Gets a module template and inserts data via placeholder => value array
   * ex.['{PLACEHOLDER}' => 'value']
   *
   * @param  string $filename Filename of template file
   * @param  array  $vars     Array of placeholders/values to insert
   * @return string           Completed markup for output
   */
  public function getTemplate(string $filename, array $tplVars = []): string {
    // Define defaults so they don't have to be entered if not needed at call
    $vars = array_merge([
      '{CLASSES}' => '',
      '{STYLES}' => '',
      '{TARGET}' => '_blank'
    ], $tplVars);

    // Get file, insert variables
    $markup = file_get_contents(__DIR__ . "/../fluency_templates/{$filename}");
    $markup = strtr($markup, $vars);

    return $markup;
  }
}
