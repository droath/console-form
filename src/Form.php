<?php

namespace Droath\ConsoleForm;

use Droath\ConsoleForm\Exception\FormException;
use Droath\ConsoleForm\Field\FieldInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Define the console form class.
 */
class Form
{
    /**
     * Form fields.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Add multiple fields to form.
     *
     * @param array $fields
     *   An array of \Droath\ConsoleForm\Field\FieldInterface objects.
     */
    public function addFields(array $fields)
    {
        foreach ($fields as $field) {
            if (!$field instanceof FieldInterface) {
                continue;
            }

            $this->addField($field);
        }

        return $this;
    }

    /**
     * Add a single field to form.
     *
     * @param \Droath\ConsoleForm\Field\FieldInterface $field
     *   The field object.
     */
    public function addField(FieldInterface $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Get all form fields.
     *
     * @return \ArrayIterator
     *    An array of form fields.
     */
    public function getFields()
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * Process form fields.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *   The console input object.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   The console output object.
     * @param \Symfony\Component\Console\Helper\QuestionHelper $helper
     *   The question helper class.
     *
     * @return array
     *   An array of field answers.
     */
    public function process(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $results = [];

        foreach ($this->fields as $field) {
            if (!$this->fieldConditionMet($field, $results)) {
                continue;
            }
            $field_name = $field->getName();

            if ($input->isInteractive()) {
                try {
                    $value = $helper->ask($input, $output, $field->asQuestion());

                    if ($field->hasSubform()) {
                        $subform = new static();
                        $field->onSubformProcess($subform, $value);

                        $results[$field_name] = $subform->process($input, $output, $helper);
                    } else {
                        $results[$field_name] = $field->formattedValue($value);
                    }
                } catch (\Exception $e) {
                    throw new FormException(trim($e->getMessage()));
                }
            }
        }

        return array_filter($results);
    }

    /**
     * Check if field condition are met.
     *
     * @param \Droath\ConsoleForm\Field\FieldInterface $field
     *   The field object.
     * @param array $results
     *   An array of field values that have already been answered.
     *
     * @return bool
     *   Return true if the all field conditions have been met; otherwise false.
     */
    protected function fieldConditionMet(FieldInterface $field, array $results)
    {
        foreach ($field->getCondition() as $field_name => $value) {
            $field_value = $this->getFieldValue($field_name, $results);

            if ($field_value !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get field value.
     *
     * @param string $field_name
     *   The field name the results are keyed by.
     * @param array $results
     *   An array of field values that have already been answered.
     *
     * @return string
     *   The value that was recorded.
     */
    protected function getFieldValue($field_name, array $results, $delimiter = '.')
    {
        // Progress the results if the field name is using a delimiter.
        foreach (explode($delimiter, $field_name) as $name) {
            if (isset($results[$name])) {
                $results = $results[$name];
            }
        }

        return !empty($results) ? $results : null;
    }
}
