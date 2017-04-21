<?php

namespace Droath\ConsoleForm;

use Droath\ConsoleForm\Exception\FormException;
use Droath\ConsoleForm\Field\FieldInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Define the console form class.
 */
class Form
{
    /**
     * [$input description].
     *
     * @var [type]
     */
    protected $input;

    /**
     * [$output description].
     *
     * @var [type]
     */
    protected $output;

    /**
     * Form fields.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Form results.
     *
     * @var array
     */
    protected $results = [];

    /**
     * [$helperSet description].
     *
     * @var array
     */
    protected $helperSet = [];

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
     * Set console input.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Set console output.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Set helper set.
     *
     * @param \Symfony\Component\Console\Helper\HelperSet $helper_set
     */
    public function setHelperSet(HelperSet $helper_set)
    {
        $this->helperSet = $helper_set;

        return $this;
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
    public function process()
    {
        if (empty($this->results)) {
            $helper = $this->helperSet->get('question');

            $input = $this->input;
            $output = $this->output;

            $results = [];
            foreach ($this->fields as $field) {
                if (!$this->fieldConditionMet($field, $results)) {
                    continue;
                }
                $field_name = $field->getName();

                if ($field->hasFieldCallback()) {
                    $field->onFieldCallback($results);
                }

                if ($input->isInteractive()) {
                    try {
                        $value = $helper->ask($input, $output, $field->asQuestion());

                        if ($field->hasSubform()) {
                            $subform = new static();
                            $field->onSubformProcess($subform, $value);

                            $results[$field_name] = $subform
                                ->setInput($input)
                                ->setOutput($output)
                                ->setHelperSet($this->helperSet)
                                ->process()
                                ->getResults();
                        } else {
                            $results[$field_name] = $field->formattedValue($value);
                        }
                    } catch (\Exception $e) {
                        throw new FormException(trim($e->getMessage()));
                    }
                }
            }

            $this->results = $results;
        }

        return $this;
    }

    /**
     * Save form results.
     *
     * @param callable $function
     *   A callback function.
     * @param bool $confirm_save
     *   Determine if we should confirm form save.
     * @param string $confirm_message
     *   The save confirmation question.
     */
    public function save(
        callable $function,
        $confirm_save = true,
        $confirm_message = 'Save results?'
    ) {
        $save = false;

        $this->process();

        if ($confirm_save) {
            $question = $this->helperSet->get('question');
            $save = $question->ask(
                $this->input,
                $this->output,
                new ConfirmationQuestion(
                    sprintf('%s [yes] ', $confirm_message)
                )
            );
        }

        if ($save && false !== $confirm_save) {
            call_user_func_array($function, [$this->getResults()]);
        }
    }

    /**
     * Get form results.
     *
     * @param bool $filter_empty
     *   Determine if form values should be filtered,
     *
     * @return array
     *   An array of form results.
     */
    public function getResults($filter_empty = true)
    {
        return $filter_empty ? array_filter($this->results) : $this->results;
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
