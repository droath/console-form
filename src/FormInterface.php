<?php

namespace Droath\ConsoleForm;

/**
 * Define the form interface.
 */
interface FormInterface
{
    /**
     * Get form machine name.
     *
     * @return string
     *   The form machine name.
     */
    public function getName();

    /**
     * Build form object.
     *
     * @return \Droath\ConsoleForm\Form
     */
    public function buildForm();
}
