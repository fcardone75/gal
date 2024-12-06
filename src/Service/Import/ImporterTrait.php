<?php


namespace App\Service\Import;


use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

trait ImporterTrait
{
    protected $substitutionMap = [];

    protected function getterForProperty($property): string
    {
        if (is_array($property)) {
            $property = $property['property'];
        }
        return implode(['get', ucfirst($property)]);
    }

    protected function setterForProperty($property): string
    {
        if (is_array($property)) {
            $property = $property['property'];
        }
        return implode(['set', ucfirst($property)]);
    }

    protected function isRowEmpty(Row $row, $columns): bool
    {
        $data = [];
        foreach ($row->getCellIterator() as $cell) {
            if (in_array($cell->getColumn(), $columns) && $value = $cell->getFormattedValue()) {
                $data[] = $value;
            }
        }
        return empty($data);
    }

    protected function getMappedCellsForEntityConfig($entityConfig): ?array
    {
        return array_map(function($config){
            return is_array($config) && isset($config['column']) ? $config['column'] : $config;
        }, array_filter($entityConfig, function($config){
            return (is_array($config) && isset($config['column'])) || is_string($config);
        }));
    }

    protected function normalizeColumnConfig($config)
    {
        if (!is_array($config)) {
            $config = ['column' => $config];
        }
        return $config;
    }

    protected function cleanCellValue($value)
    {
        return str_replace(
            array_keys($this->substitutionMap),
            array_values($this->substitutionMap),
            $value
        );
    }

    /**
     * @param Cell $cell
     * @param $config
     * @return string
     */
    protected function getCellValueUsingConfig(Cell $cell, $config): string
    {
        if (isset($config['input_type'])) {
            switch ($config['input_type']) {
                case 'float':
                    if ($cell->getDataType() !== 's' && isset($config['xls_number_format'])) {
                        $cell->getStyle()->getNumberFormat()->setFormatCode($config['xls_number_format']);
                    }
                    break;
                case 'date':
                case 'datetime':
                    $formattedValue = $cell->getFormattedValue();

                    if ((int) $formattedValue === 0) {
                        return '';
                    }

                    if ($cell->getDataType() !== 's' && isset($config['xls_number_format'])) {
                        $cell->getStyle()->getNumberFormat()->setFormatCode($config['xls_number_format']);
                    }
                    break;
            }
        }

        return trim($cell->getFormattedValue());
    }
}
