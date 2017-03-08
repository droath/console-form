# Console Form

[![Build Status](https://travis-ci.org/droath/console-form.svg?branch=master)](https://travis-ci.org/droath/console-form)

A simple form solution when using the Symfony console component.

There are two ways forms can be created, one is using the standalone method which usually takes place in the command ***execute*** method.

Or by taking advantage of the auto discovery feature so you can reuse forms throughout the project, which keeps the form logic decoupled from the command it's executed on.


## Getting Started

First, you'll need to download the console form library using composer:

```bash
composer require droath/console-form:~0.0.2
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
        $helper = $this->getHelper('question');

        $form = $this->getHelper('form')->getForm();

        $form->addFields([
            (new TextField('project', 'Project name'))
                ->setDefault('Demo Project'),
            (new SelectField('version', 'Project Version'))
                ->setOptions(['7.x', '8.x'])
                ->setDefault('8.x'),
        ]);

        $results = $form->process($input, $output, $helper);

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
        $helper = $this->getHelper('question');

        $form = $this->getHelper('form')
            ->getFormByName('project.form.setup');

        $results = $form->process($input, $output, $helper);

        var_dump($results)
    }

    ...
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

        $helper = $this->getHelper('question');
        $results = $form->process($input, $output, $helper);
```

The "More options for 7.x version" text field will only be shown if the 7.x version was selected in the previous question.


### Process

The setProcess() method requires a callback function to be set, which can be an anonymous function or any valid PHP callable. The process function is invoked during the form process lifecycle.

The callable function is given two arguments, first is the inputed value; the other is the result array. The result array can be set to an alternative value based on a more complex structure.

Below is an example of the process callback which is being used to support subforms.

```php
<?php
...

    $form->addFields([
        (new TextField('project', 'Project name'))
            ->setDefault('Demo Project'),
        (new SelectField('version', 'Project Version'))
            ->setOptions(['7.x', '8.x'])
            ->setDefault('8.x'),
        (new BooleanField('host', 'Setup host'))
            ->setProcess(function ($value, &$result) use ($input, $output) {
                if ($value == 1) {
                    $form = (new Form())
                        ->addFields([
                            new TextField('url', 'Host URL'),
                            new BooleanField('on_startup', 'Launch on startup'),
                        ]);
                    $helper = $this->getHelper('question');
                    $result = $form->process($input, $output, $helper);
                }
            }),
    ]);

    $helper = $this->getHelper('question');
    $results = $form->process($input, $output, $helper);

```

As you can see the host boolean field doesn't really need to collect any data, it's only needed to determine if more form options should be collected. The results returned from the subform are saved back into the $result variable, which is passed in by reference.


