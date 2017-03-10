<?php

namespace Droath\ConsoleForm;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form';
    }

    /**
     * Get form instance.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   The console input.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   The console output.
     *
     * @return \Droath\ConsoleForm\Form
     *   The form instance.
     */
    public function getForm(InputInterface $input, OutputInterface $output)
    {
        return $this->createInstance($input, $output);
    }

    /**
     * Get form by name.
     *
     * @param string $name
     *   The form name.
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   The console input.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   The console output.
     *
     * @return \Droath\ConsoleForm\Form
     *   The form instance.
     */
    public function getFormByName($name, InputInterface $input, OutputInterface $output)
    {
        if (!isset($this->forms[$name])) {
            throw new \Exception(
                sprintf('Unable to find %s form.', $name)
            );
        }

        return $this->createInstance($input, $output, $this->forms[$name]);
    }

    /**
     * Create form instance.
     *
     * @param \Droath\ConsoleForm\Form|null $form
     *   An already created form instance or null if one should be created.
     *
     * @return \Droath\ConsoleForm\Form
     */
    protected function createInstance(
        InputInterface $input,
        OutputInterface $output,
        Form $form = null
    ) {
        if (!isset($form)) {
            $form = new Form();
        }

        return $form
            ->setInput($input)
            ->setOutput($output)
            ->setHelperSet($this->getHelperSet());
    }
}
