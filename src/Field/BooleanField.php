<?php

namespace Droath\ConsoleForm\Field;

/**
 * Define the form boolean field.
 */
class BooleanField extends Field implements FieldInterface
{

    /**
     * Field regular expression.
     *
     * @var string
     */
    protected $regex = '/^y/i';

    /**
     * Field default value.
     *
     * @var bool
     */
    protected $default = true;

    /**
     * {@inheritdoc}
     */
    public function dataType()
    {
        return 'boolean';
    }

    /**
     * Set boolean answer regular expression.
     *
     * @param string $regex
     *   The regular expression used.
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function questionClass()
    {
        return '\Symfony\Component\Console\Question\ConfirmationQuestion';
    }

    /**
     * {@inheritdoc}
     */
    public function questionClassArgs()
    {
        return [$this->formattedLabel(), $this->default, $this->regex];
    }

    /**
     * Override \Droath\ConsoleForm\Field::formattedLabel.
     */
    protected function formattedLabel()
    {
        // Convert boolean default into a readable format.
        $default = $this->default ? 'yes' : 'no';

        $label[] = $this->label;
        $label[] = isset($default) ? "<fg=yellow>[$default]</>: " : ': ';

        return implode(' ', $label);
    }
}
