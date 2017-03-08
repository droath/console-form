<?php

namespace Droath\ConsoleForm\Field;

/**
 * Define field interface.
 */
interface FieldInterface
{
    /**
     * The data type the field holds.
     *
     * @return string
     */
    public function dataType();

    /**
     * Field question class name.
     *
     * @return string
     *   The question class that best represents the field structure.
     */
    public function questionClass();

    /**
     * Field question class constructor arguments.
     *
     * @return array
     *   An array of constructor arguments.
     */
    public function questionClassArgs();
}
