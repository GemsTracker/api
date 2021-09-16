<?php


namespace Gems\Rest\Log\Formatter;


use Laminas\Log\Formatter\Simple;

/**
 * Fixes the way multidimensional arrays are written to JSON in the log
 *
 * Class SimpleMulti
 * @package Gems\Rest\Log\Formatter
 */
class SimpleMulti extends Simple
{
    /**
     * Formats data to be written by the writer.
     *
     * @param array $event event data
     * @return array
     */
    public function format($event)
    {
        foreach ($event as $key => $value) {
            if ($key == 'extra' && is_array($value)) {
                continue;
            }
            $event[$key] = $this->normalize($value);
        }

        $output = $this->format;

        foreach ($event as $name => $value) {
            if ('extra' == $name && is_array($value) && count($value)) {
                $value = $this->normalize($value);
            } elseif ('extra' == $name) {
                // Don't print an empty array
                $value = '';
            }
            $output = str_replace("%$name%", $value, $output);
        }

        if (isset($event['extra']) && empty($event['extra'])
            && false !== strpos($this->format, '%extra%')
        ) {
            $output = rtrim($output, ' ');
        }
        return $output;
    }
}
