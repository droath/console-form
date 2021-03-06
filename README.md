# Console Form

[![Build Status](https://travis-ci.org/droath/console-form.svg?branch=master)](https://travis-ci.org/droath/console-form)

A simple form solution when using the Symfony console component.

There are two ways forms can be created, one is using the standalone method which usually takes place in the command ***execute*** method.

Or by taking advantage of the auto discovery feature so you can reuse forms throughout the project, which keeps the form logic decoupled from the command it's executed on.


## Getting Started

First, you'll need to download the console form library using composer:

```bash
composer require droath/console-form
```

Set the **\Droath\ConsoleForm\FormHelper** as a helper class within the console application HelperSet:

```php
<?php

$application = new \Symfony\Component\Console\Application('Project-Demo', '0.0.1');

$application->getHelperSet()
    ->set(new \Droath\ConsoleForm\FormHelper());
...
```

### Auto Discovery

If you decide to use the auto discovery feature you'll need to use the **\Droath\ConsoleForm\FormDiscovery** class:

```php
<?php
...

$formDiscovery = (new \Droath\ConsoleForm\FormDiscovery())
    ->discover(__DIR__ . '/Form', '\Droath\Project\Form');

$application->getHelperSet()
    ->set(new \Droath\ConsoleForm\FormHelper($formDiscovery));
...
```
The **\Droath\ConsoleForm\FormDiscovery::discover()** method takes two arguments, the first argument is either a single directory or an array or directories. These directories are searched for form classes that have implemented the **\Droath\ConsoleForm\FormInterface** interface. The second argument is the class namespace.

Here is an example of what the form class needs to implement so it's found by the auto discovery feature:

```php
<?php

namespace Droath\Project\Form;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ConsoleForm\FormInterface;

/**
 * Define project setup form.
 */
class ProjectSetup implements FormInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'project.form.setup';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm()
    {
        return (new Form())
            ->addField(new TextField('name', 'What is your name?'))
            ->addField((new SelectField('gender', 'What is your gender?'))
                ->setOptions(['male', 'female', 'other']))
            ->addField(new BooleanField('send_newletter', 'Email me the weekly newsletter'));
    }
}
```

### Form Basics

Interacting with the form helper within the command class. Either you can create a form using the standalone method or retrieve the form using the name that was defined **(auto discovery needs to be implemented)**.

First, I'll show you the standalone method on which you can create a form:

```php
<?php

namespace Droath\Project\Command;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Initialize extends Command
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize project config.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $form = $this->getHelper('form')
            ->getForm($input, $output);

        $form->addFields([
            (new TextField('project', 'Project name'))
                ->setDefault('Demo Project'),
            (new SelectField('version', 'Project Version'))
                ->setOptions(['7.x', '8.x'])
                ->setDefault('8.x'),
        ]);

        $results = $form
            ->process()
            ->getResults();

        var_dump($results)
    }
    ...
```

If using **auto discovery** you can simply just retrieve the form by name:

```php
<?php

namespace Droath\Project\Command;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Initialize extends Command
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialize project config.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $form = $this->getHelper('form')
            ->getFormByName('project.form.setup', $input, $output);

        $results = $form
            ->process()
            ->getResults();

        var_dump($results)
    }

    ...
```

### Form Save

Many of times forms need to save their results to a filesystem. The form save() method displays a confirmation message asking the user to save the results (which is configurable). If input is true, then the save callable is invoked; otherwise it's disregarded.

```php
<?php

...
    $form
        ->addFields([
            (new TextField('project', 'Project name'))
                ->setDefault('Demo Project'),
            (new SelectField('version', 'Project Version'))
                ->setOptions(['7.x', '8.x'])
                ->setDefault('8.x'),
        ])
        ->save(function($results) {
            // Save results to filesystem or remote source.
            // Don't need to call process() as it's done inside the save method.
        });
```

## Form Field Groups

There might come a time when you need to group fields together. One of the advantages would be having the ability to collect multiple inputs for a grouping of fields. As you can see in the example below, the `setLoopUntil()` provides the result array that was last given, these values can help determine if you should stop collecting data. Return `false` to stop field group iteration, otherwise set to `true`.

```php
<?php
...

    $form->addFields([
        (new TextField('name', 'Project Name')),
        (new FieldGroup('environments'))
            ->addFields([
                (new TextField('ssh_label', 'SSH Label'))
                    ->setRequired(false),
                (new TextField('ssh_host', 'SSH Host'))
                    ->setRequired(false),
                (new Textfield('ssh_uri', 'SSH URI'))
                    ->setRequired(false)
            ])
            ->setLoopUntil(function($result) {
                if (!isset($result['ssh_label'])) {
                    return false;
                }
                return true;
            })
    ]);

    $results = $form
        ->process()
        ->getResults();
```

## Form Fields

There are three basic field types which are:

- Text
- Select
- Boolean

All field types derive from the base field class, so most fields have similar methods and features.

Below are some examples of the most useful field methods:

### Condition

The setCondition() method allows a field to be shown based on what value was previously inputted by the user. This method can be set multiple times if you require more conditions to be met for a given field.

```php
<?php
...
    $form
        ->addFields([
            (new TextField('project', 'Project name'))
                ->setDefault('Demo Project'),
            (new SelectField('version', 'Project Version'))
                ->setOptions(['7.x', '8.x'])
                ->setDefault('8.x'),
            (new TextField('another_option', 'More options for 7.x version'))
                ->setCondition('version', '7.x'),
        ]);

    $results = $form
        ->process()
        ->getResults();
```

The "More options for 7.x version" text field will only be shown if the 7.x version was selected in the previous question.

### Field Callback

The setFieldCallback() method requires a single callback function, which can be any valid PHP callable. The callback will be invoked during the form process lifecycle. The field callback receives two arguments, the first argument is the field instance. The second is an array of results for all previous questions.

Now you can set additional methods for a given field instance based on the results for a previous question(s). As you can see in the example below the select options are added based on the results retrieved from the "Project Name" text field.

```php
<?php
...

    $form
        ->addFields([
            (new TextField('name', 'Project Name')),
            (new SelectField('version', 'Project Version'))
                ->setFieldCallback(function ($field, $results) {
                    if ($results['name'] === 'My Project') {
                        $field->setOptions([
                            '7' => '7x',
                            '8' => '8x'
                        ]);
                    } else {
                        $field->setOptions([
                            '11' => '11x',
                            '12' => '12x'
                        ]);
                    }
                }),
        ]);

```


### Subform

The setSubform() method requires a callback function to be set, which can be an anonymous function or any valid PHP callable. The subform function is invoked during the original form process lifecycle.

The subform callable is given two arguments, the first argument is the subform instance and the second is the inputed value. The callable doesn't need to return any data. Also, the original form process takes care of processing the subform.

```php
<?php
...

    $form
        ->addFields([
            (new BooleanField('questions', 'Ask questions?'))
                ->setSubform(function ($subform, $value) {
                    if ($value === true) {
                        $subform->addFields([
                            (new TextField('how_old', 'How old are you?')),
                            (new TextField('location', 'Where do you live?')),
                        ]);
                    }
                }),
        ]);

        $results = $form
            ->process()
            ->getResults();

```
