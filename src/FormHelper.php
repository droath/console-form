<?php

namespace Droath\ConsoleForm;

use Symfony\Component\Console\Helper\Helper;

/**
 * Define the form helper.
 */
class FormHelper extends Helper
{
    /**
     * All registered forms.
     *
     * Forms are usually found during discovery or passed in manually.
     *
     * @var array
     */
    protected $forms;

    /**
     * Constructor for form helper.
     *
     * @param array $forms
     *   An array of discovered forms.
     */
    public function __construct(array $forms = [])
    {
        $this->forms = $forms;
    }

    /**
     * Get a basic form object.
     *
     * @return \Droath\ConsoleForm\Form
     */
    public function getForm()
    {
        return new Form();
    }

    /**
     * Get form by name.
     *
     * @param string $name
     *   The form name.
     *
     * @return \Droath\ConsoleForm\Form
     *   The form object.
     */
    public function getFormByName($name)
    {
        if (!isset($this->forms[$name])) {
            throw new \Exception(
                sprintf('Unable to find %s form.', $name)
            );
        }

        return $this->forms[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form';
    }
}
