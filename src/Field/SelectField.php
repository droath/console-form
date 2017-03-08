<?php

namespace Droath\ConsoleForm\Field;

/**
 * Define the form select field.
 */
class SelectField extends Field implements FieldInterface
{
    /**
     * Define select options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function dataType()
    {
        return 'string';
    }

    /**
     * Set select options.
     *
     * @param array $options
     *   An array select options.
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function questionClass()
    {
        return '\Symfony\Component\Console\Question\ChoiceQuestion';
    }

    /**
     * {@inheritdoc}
     */
    public function questionClassArgs()
    {
        return [$this->formattedLabel(), $this->options, $this->default];
    }
}
