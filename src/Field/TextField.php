<?php

namespace Droath\ConsoleForm\Field;

/**
 * Define the text field.
 */
class TextField extends Field implements FieldInterface
{
    /**
     * {@inheritdoc}
     */
    public function dataType()
    {
        return 'string';
    }

    /**
     * {@inheritdoc}
     */
    public function questionClassArgs()
    {
        return [$this->formattedLabel(), $this->default];
    }

    /**
     * {@inheritdoc}
     */
    public function questionClass()
    {
        return '\Symfony\Component\Console\Question\Question';
    }
}
