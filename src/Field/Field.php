<?php

namespace Droath\ConsoleForm\Field;

use Droath\ConsoleForm\FieldDefinitionInterface;
use Droath\ConsoleForm\Form;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Define the form field base class.
 */
abstract class Field implements FieldDefinitionInterface
{
    /**
     * Field name.
     *
     * @var string
     */
    protected $name;

    /**
     * Field label.
     *
     * @var string
     */
    protected $label;

    /**
     * Field default value.
     *
     * @var string
     */
    protected $default;

    /**
     * Field hidden.
     *
     * @var bool
     */
    protected $hidden;

    /**
     * Field subform callback.
     *
     * @var callable
     */
    protected $subform;

    /**
     * Field required flag.
     *
     * @var bool
     */
    protected $required;

    /**
     * Field max attempt.
     *
     * @var int
     */
    protected $maxAttempt;

    /**
     * Field normalizer.
     *
     * @var callable
     */
    protected $normalizer;

    /**
     * Field conditions.
     *
     * @var array
     */
    protected $condition = [];

    /**
     * Field validation.
     *
     * @var array
     */
    protected $validation = [];

    /**
     * Field callback.
     *
     * @var callable
     */
    protected $fieldCallback;

    /**
     * Field constructor.
     *
     * @param string $name
     *   The field name.
     * @param string $label
     *   The field label.
     * @param bool $required
     *   Set if the field is required.
     * @param int $max_attempt
     *   Set the field max attempts.
     */
    public function __construct($name, $label = null, $required = true, $max_attempt = 3)
    {
        $this
            ->setName($name)
            ->setLabel($label)
            ->setRequired($required)
            ->setMaxAttempt($max_attempt);
    }

    /**
     * Set field name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = strtr($name, ' ', '_');

        return $this;
    }

    /**
     * Set field human readable label.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set field default value.
     *
     * @param string $default
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Set field required.
     *
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = (bool) $required;

        return $this;
    }

    /**
     * Set field max attempt.
     *
     * @param int $max_attempt
     */
    public function setMaxAttempt($max_attempt)
    {
        $this->maxAttempt = $max_attempt;

        return $this;
    }

    /**
     * Set field hidden.
     *
     * @param bool $hidden
     *   If true the inputted value will be hidden; otherwise it will be shown.
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Set field validation.
     *
     * @param callable $function
     *   A callback function; throw an exception if validation error occurs.
     */
    public function setValidation(callable $function)
    {
        $this->validation[] = $function;

        return $this;
    }

    /**
     * Set field normalizer.
     *
     * @param callable $function
     *   A callback function.
     */
    public function setNormalizer(callable $function)
    {
        $this->normalizer = $function;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Is field required.
     *
     * @return bool
     */
    public function isRequire()
    {
        return (bool) $this->required;
    }

    /**
     * Get field condition.
     *
     * @return array
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Has field condition.
     *
     * @return bool
     */
    public function hasCondition()
    {
        return !empty($this->condition);
    }

    /**
     * Set field callback.
     *
     * @param callable $callback
     *   A callable function.
     */
    public function setFieldCallback(callable $function)
    {
        $this->fieldCallback = $function;

        return $this;
    }

    /**
     * Has field callback.
     *
     * @return bool
     */
    public function hasFieldCallback()
    {
        return is_callable($this->fieldCallback);
    }

    /**
     * Set field conditions.
     *
     * @param string $field_name
     *   The field name. Use dot notation if you need to check conditions that
     *   are nested.
     * @param string $value
     *   The field value that needs to be met.
     */
    public function setCondition($field_name, $value, $operation = '=')
    {
        $this->condition[$field_name] = [
            'value' => $value,
            'operation' => $operation
        ];

        return $this;
    }

    /**
     * Has subform callback function.
     *
     * @return bool
     */
    public function hasSubform()
    {
        return isset($this->subform) && is_callable($this->subform);
    }

    /**
     * Set subform callback function.
     *
     * @param callable $function
     *   The function to callback.
     */
    public function setSubform(callable $function)
    {
        $this->subform = $function;

        return $this;
    }

    /**
     * React on a field subform being processed.
     *
     * @param \Droath\ConsoleForm\Form $form
     *   The form object.
     * @param string value
     *   The field value.
     */
    public function onSubformProcess(Form $form, $value)
    {
        call_user_func_array($this->subform, [$form, $value]);
    }

    /**
     * React on a field before the question class is invoked.
     *
     * @param array $results
     *   The form results from previous questions.
     */
    public function onFieldCallback(array $results)
    {
        call_user_func_array($this->fieldCallback, [$this, $results]);
    }

    /**
     * Field as question instance.
     *
     * @return \Symfony\Component\Console\Question\Question
     */
    public function asQuestion()
    {
        $instance = $this->questionClassInstance()
            ->setMaxAttempts($this->maxAttempt);

        // Set question instance hidden if defined by the field.
        if ($this->hidden) {
            $instance->setHidden(true);
        }

        // Set validation callback if field is required.
        if ($this->isRequire()) {
            $this->setValidation(function ($answer) {
                if ($answer == ''
                    && $this->dataType() !== 'boolean') {
                    throw new \Exception('Field is required.');
                }
            });
        }

        // Set question instance validator callback.
        $instance->setValidator(function ($answer) {

            // Iterate over all field validation callbacks.
            foreach ($this->validation as $callback) {
                if (!is_callable($callback)) {
                    continue;
                }

                $callback($answer);
            }

            return $answer;
        });

        // Set question normalizer based on the field normalizer. If a default
        // normalizer is being set from the question class then setting the
        // normalizer will trump it's execution, as only one normalizer can be
        // set per question instance.
        if (isset($this->normalizer)
            && is_callable($this->normalizer)) {
            $instance->setNormalizer($this->normalizer);
        }

        return $instance;
    }

    /**
     * Field formatted value.
     *
     * @param string $value
     *   User inputted field value.
     *
     * @return mixed
     *   The formatted value to output.
     */
    public function formattedValue($value)
    {
        return $value;
    }

    /**
     * Field formatted label.
     *
     * @return string
     *   The formatted field label.
     */
    protected function formattedLabel()
    {
        $label[] = $this->label;
        $label[] = isset($this->default) ? "<fg=yellow>[$this->default]</>: " : ': ';

        return implode(' ', $label);
    }

    /**
     * Question class instance.
     *
     * @return \Symfony\Component\Console\Question\Question
     *   An instantiated question class.
     */
    protected function questionClassInstance()
    {
        $classname = $this->questionClass();

        if (!class_exists($classname)) {
            throw new \Exception('Invalid question class.');
        }

        $instance = (new \ReflectionClass($classname))
            ->newInstanceArgs($this->questionClassArgs());

        if (!$instance instanceof \Symfony\Component\Console\Question\Question) {
            throw new \Exception('Invalid question class instance');
        }

        return $instance;
    }
}
