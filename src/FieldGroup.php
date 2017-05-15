<?php

namespace Droath\ConsoleForm;

use Droath\ConsoleForm\FieldDefinitionInterface;
use Droath\ConsoleForm\Field\Field;
use Droath\ConsoleForm\Field\FieldInterface;

/**
 * Define field group.
 */
class FieldGroup implements FieldDefinitionInterface
{
    /**
     * Field group name.
     *
     * @var string
     */
    protected $name;

    /**
     * Field group fields
     *
     * @var array
     */
    protected $groupFields = [];

    /**
     * Loop until callback.
     *
     * @var callable
     */
    protected $loopUntil;

    /**
     * Field group constructor.
     *
     * @param string $name
     *   The field group name.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Set field group name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add fields to the field group.
     *
     * @param array $fields
     *   An array of fields.
     */
    public function addFields(array $fields)
    {
        foreach ($fields as $field) {
            if (!$field instanceof FieldDefinitionInterface) {
                continue;
            }
            $this->addField($field);
        }

        return $this;
    }

    /**
     * Add a single field to the field group.
     *
     * @param \Droath\ConsoleForm\FieldDefinitionInterface $field
     *   The field definition implementation.
     */
    public function addField(FieldDefinitionInterface $field)
    {
        $this->groupFields[] = $field;

        return $this;
    }

    /**
     * Get group fields.
     *
     * @return array
     *   An array of fields attached to the group.
     */
    public function getGroupFields()
    {
        return $this->groupFields;
    }

    /**
     * Set the loop until callable.
     *
     * @param callable $function
     *   An callable function.
     */
    public function setloopUntil(callable $function)
    {
        $this->loopUntil = $function;

        return $this;
    }

    /**
     * Has a loop until callable.
     *
     * @return bool
     *   Determine if the loop until callable has been set.
     */
    public function hasLoopUntil()
    {
        return isset($this->loopUntil) && is_callable($this->loopUntil);
    }

    /**
     * Loop until execute callback.
     *
     * @param array $args
     *   An array of arguments to pass along.
     *
     * @return bool
     *   Return a boolean based on if field group should continue
     *   to loop over the field group.
     */
    public function loopUntil(array $args)
    {
        return call_user_func_array($this->loopUntil, $args);
    }
}
