<?php

namespace Grocy\Helpers;

use Exception;

abstract class BaseBarcodeLookupPlugin
{
    // That's a "self-referencing constant" and forces the child class to define it
    public const PLUGIN_NAME = self::PLUGIN_NAME;

    final public function __construct(protected $Locations, protected $QuantityUnits, protected $userSettings)
    {
    }

    final public function lookup($barcode)
    {
        $pluginOutput = $this->executeLookup($barcode);

        if ($pluginOutput === null) {
            return $pluginOutput;
        }

        // Plugin must return an associative array
        if (!is_array($pluginOutput)) {
            throw new Exception('Plugin output must be an associative array');
        }

        if (!isAssociativeArray($pluginOutput)) {
            // $pluginOutput is at least an indexed array here
            throw new Exception('Plugin output must be an associative array');
        }

        // Check for minimum needed properties
        $minimunNeededProperties = [
            'name',
            'location_id',
            'qu_id_purchase',
            'qu_id_stock',
            '__qu_factor_purchase_to_stock',
            '__barcode'
        ];

        foreach ($minimunNeededProperties as $prop) {
            if (!array_key_exists($prop, $pluginOutput)) {
                throw new Exception('Plugin output does not provide needed property ' . $prop);
            }
        }

        // $pluginOutput contains all needed properties here

        // Check if referenced entity ids are valid
        $locationId = $pluginOutput['location_id'];
        if (findObjectInArrayByPropertyValue($this->Locations, 'id', $locationId) === null) {
            throw new Exception(sprintf('Provided location_id (%s) is not a valid location id', $locationId));
        }

        $quIdPurchase = $pluginOutput['qu_id_purchase'];
        if (findObjectInArrayByPropertyValue($this->QuantityUnits, 'id', $quIdPurchase) === null) {
            throw new Exception(sprintf('Provided qu_id_purchase (%s) is not a valid quantity unit id', $quIdPurchase));
        }

        $quIdStock = $pluginOutput['qu_id_stock'];
        if (findObjectInArrayByPropertyValue($this->QuantityUnits, 'id', $quIdStock) === null) {
            throw new Exception(sprintf('Provided qu_id_stock (%s) is not a valid quantity unit id', $quIdStock));
        }

        $quFactor = $pluginOutput['__qu_factor_purchase_to_stock'];
        if (empty($quFactor) || !is_numeric($quFactor)) {
            throw new Exception('Provided __qu_factor_purchase_to_stock is empty or not a number');
        }

        return $pluginOutput;
    }

    abstract protected function executeLookup($barcode);
}
