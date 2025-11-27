<?php

namespace Grocy\Services;

use Exception;

class UserfieldsService extends BaseService
{
    public const USERFIELD_TYPE_CHECKBOX = 'checkbox';
    public const USERFIELD_TYPE_DATE = 'date';
    public const USERFIELD_TYPE_DATETIME = 'datetime';
    public const USERFIELD_TYPE_NUMBER_INT = 'number-integral';
    public const USERFIELD_TYPE_NUMBER_DECIMAL = 'number-decimal';
    public const USERFIELD_TYPE_NUMBER_CURRENCY = 'number-currency';
    public const USERFIELD_TYPE_FILE = 'file';
    public const USERFIELD_TYPE_IMAGE = 'image';
    public const USERFIELD_TYPE_LINK = 'link';
    public const USERFIELD_TYPE_LINK_WITH_TITLE = 'link-with-title';
    public const USERFIELD_TYPE_PRESET_CHECKLIST = 'preset-checklist';
    public const USERFIELD_TYPE_PRESET_LIST = 'preset-list';
    public const USERFIELD_TYPE_SINGLE_LINE_TEXT = 'text-single-line';
    public const USERFIELD_TYPE_SINGLE_MULTILINE_TEXT = 'text-multi-line';

    protected $openApiSpec;

    public function getAllFields()
    {
        return $this->getDatabase()->userfields()->orderBy('name', 'COLLATE NOCASE')->fetchAll();
    }

    public function getAllValues($entity)
    {
        if (!$this->isValidExposedEntity($entity)) {
            throw new Exception('Entity does not exist or is not exposed');
        }

        $this->getFields($entity);
        return $this->getDatabase()->userfield_values_resolved()->where('entity', $entity)->orderBy('name', 'COLLATE NOCASE')->fetchAll();
    }

    public function getEntities()
    {
        $exposedDefaultEntities = $this->getOpenApiSpec()->components->schemas->ExposedEntity->enum;
        $userEntities = [];
        $specialEntities = ['users'];

        foreach ($this->getDatabase()->userentities()->orderBy('name', 'COLLATE NOCASE') as $userentity) {
            $userEntities[] = 'userentity-' . $userentity->name;
        }

        $entitiesSorted = array_merge($exposedDefaultEntities, $userEntities, $specialEntities);
        sort($entitiesSorted);
        return $entitiesSorted;
    }

    public function getField($fieldId)
    {
        return $this->getDatabase()->userfields($fieldId);
    }

    public function getFieldTypes()
    {
        return getClassConstants(\Grocy\Services\UserfieldsService::class);
    }

    public function getFields($entity)
    {
        if (!$this->isValidExposedEntity($entity)) {
            throw new Exception('Entity does not exist or is not exposed');
        }

        return $this->getDatabase()->userfields()->where('entity', $entity)->orderBy('sort_number')->orderBy('name', 'COLLATE NOCASE')->fetchAll();
    }

    public function getValues($entity, $objectId)
    {
        if (!$this->isValidExposedEntity($entity)) {
            throw new Exception('Entity does not exist or is not exposed');
        }

        $userfields = $this->getFields($entity);
        $userfieldValues = $this->getDatabase()->userfield_values_resolved()->where('entity = :1 AND object_id = :2', $entity, $objectId)->orderBy('name', 'COLLATE NOCASE')->fetchAll();

        $userfieldKeyValuePairs = [];
        foreach ($userfields as $userfield) {
            $value = findObjectInArrayByPropertyValue($userfieldValues, 'name', $userfield->name);
            if ($value) {
                $userfieldKeyValuePairs[$userfield->name] = $value->value;
            } else {
                $userfieldKeyValuePairs[$userfield->name] = null;
            }
        }

        return $userfieldKeyValuePairs;
    }

    public function setValues($entity, $objectId, $userfields)
    {
        if (!$this->isValidExposedEntity($entity)) {
            throw new Exception('Entity does not exist or is not exposed');
        }

        foreach ($userfields as $key => $value) {
            $fieldRow = $this->getDatabase()->userfields()->where('entity = :1 AND name = :2', $entity, $key)->fetch();

            if ($fieldRow === null) {
                throw new Exception("Field $key is not a valid userfield of the given entity");
            }

            $fieldId = $fieldRow->id;

            $alreadyExistingEntry = $this->getDatabase()->userfield_values()->where('field_id = :1 AND object_id = :2', $fieldId, $objectId)->fetch();

            if ($alreadyExistingEntry) { // Update
                $alreadyExistingEntry->update([
                    'value' => $value
                ]);
            } else { // Insert
                $newRow = $this->getDatabase()->userfield_values()->createRow([
                    'field_id' => $fieldId,
                    'object_id' => $objectId,
                    'value' => $value
                ]);
                $newRow->save();
            }
        }
    }

    protected function getOpenApiSpec()
    {
        if ($this->openApiSpec == null) {
            $this->openApiSpec = json_decode(file_get_contents(__DIR__ . '/../grocy.openapi.json'));
        }

        return $this->openApiSpec;
    }

    private function isValidExposedEntity($entity)
    {
        return in_array($entity, $this->getEntities());
    }
}
