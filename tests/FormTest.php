<?php

namespace Droath\ConsoleForm\Tests;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use Droath\ConsoleForm\FormHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FormTest extends TestCase
{
    /**
     * Form instance.
     *
     * @var \Droath\ConsoleForm\Form
     */
    protected $form;

    protected function setUp()
    {
        $form_helper = $this->getHelperSet()->get('form');

        $this->form = $form_helper->getForm(
            new ArrayInput([]),
            new NullOutput()
        );
    }

    public function testAddFields()
    {
        $this->form->addFields([
            new TextField('item_1', 'Item 1'),
            new TextField('item_2', 'Item 2'),
        ]);

        $this->assertCount(2, $this->form->getFields());
    }

    public function testGetFields()
    {
        $fields = $this->form->getFields();
        $this->assertInstanceOf('\ArrayIterator', $fields);
    }

    public function testInterativeTextFieldProcess()
    {
        $this->form->addFields([
            new TextField('name', 'Name'),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'Steve Jobs',
        ]);

        $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertContains('Steve Jobs', $results['name']);
    }

    public function testInterativeBooleanFieldProcess()
    {
        $this->form->addFields([
            new BooleanField('like_your_job', 'Do you like your job?'),
            new BooleanField('like_your_location', 'Do you like your current location?'),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'yes',
            'no',
        ]);

        $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertEquals('true', $results['like_your_job']);
        $this->assertEquals('false', $results['like_your_location']);
    }

    public function testInterativeSelectFieldProcess()
    {
        $this->form->addFields([
            (new SelectField('favorite_color', 'Favorite color?'))
                ->setOptions(['red', 'blue', 'green']),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'blue',
        ]);

        $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertEquals('blue', $results['favorite_color']);
    }

    /**
     * @expectedException \Droath\ConsoleForm\Exception\FormException
     */
    public function testMissingSelectFieldOptions()
    {
        $this->form->addFields([
            (new SelectField('favorite_color', 'Favorite color?')),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'blue',
        ]);

        $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->fail("Form exception should've been thrown.");
    }

    /**
     * @expectedException \Droath\ConsoleForm\Exception\FormException
     */
    public function testInterativeSetRequired()
    {
        $this->form->addFields([
            (new TextField('project_name', 'Project Name'))
                ->setRequired(true),
        ]);

        $helperSet = $this->getHelperSetMockWithInput("\n");

        $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->fail("Form exception should've been thrown.");
    }

    public function testInterativeSetFieldDefault()
    {
        $this->form->addFields([
            (new TextField('project_name', 'Project Name'))
                ->setDefault('Demo Project'),
            (new BooleanField('happiness', 'Are you happy?'))
                    ->setDefault(false),
            (new SelectField('favorite_color', 'Favorite color?'))
                ->setOptions(['red', 'blue', 'green'])
                ->setDefault('green'),
        ]);

        $helperSet = $this->getHelperSetMockWithInput("\n\n\n");

        $results = $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertEquals('false', $results['happiness']);
        $this->assertEquals('green', $results['favorite_color']);
        $this->assertEquals('Demo Project', $results['project_name']);
    }

    public function testInteractiveSetNormalizer()
    {
        $this->form->addFields([
            (new TextField('project_name', 'Project Name'))
                ->setNormalizer(function ($value) {
                    return strtr(strtolower($value), ' ', '-');
                }),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'Hacker Box',
        ]);

        $results = $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertEquals('hacker-box', $results['project_name']);
    }

    public function testInteractiveSetSubform()
    {
        $this->form->addFields([
            (new BooleanField('questions', 'Ask questions?'))
                ->setSubform(function ($form, $value) {
                    if ($value === true) {
                        $form->addFields([
                            (new TextField('how_old', 'How old are you?')),
                            (new TextField('location', 'Where do you live?')),
                        ]);
                    }
                }),
        ]);

        $helperSet = $this->getHelperSetMockWithInput(
            ['yes'],
            [1000, 'cave']
        );

        $results = $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertCount(2, $results['questions']);
        $this->assertEquals(1000, $results['questions']['how_old']);
        $this->assertEquals('cave', $results['questions']['location']);
    }

    public function testInteractiveSetCondition()
    {
        $this->form->addFields([
            (new TextField('project_name', 'Project Name')),
            (new TextField('project_version', 'Project Version'))
                ->setCondition('project_name', 'Demo'),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'Demoooo',
            '8.x',
        ]);

        $results = $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertEquals('Demoooo', $results['project_name']);
        $this->assertArrayNotHasKey('project_version', $results);
    }

    public function testInteractiveSetFieldCallabck()
    {
        $this->form->addFields([
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

        $helperSet = $this->getHelperSetMockWithInput([
            'My Project',
            8
        ]);

        $results = $results = $this->form
            ->setHelperSet($helperSet)
            ->process()
            ->getResults();

        $this->assertEquals(8, $results['version']);
        $this->assertEquals('My Project', $results['name']);
    }

    public function testInterativeFormSave()
    {
        $this->form->addFields([
            (new TextField('name', 'Project Name')),
            (new TextField('version', 'Project Version')),
        ]);

        $helperSet = $this->getHelperSetMockWithInput(
            ['Demoooo', '8.x'],
            ['yes']
        );
        $data = [];

        $this->form
            ->setHelperSet($helperSet)
            ->save(function ($results) use (&$data) {
                $data = $results;
            });

        $this->assertEquals('Demoooo', $data['name']);
        $this->assertEquals('8.x', $data['version']);
    }

    public function testInterativeNoFormSave()
    {
        $this->form->addFields([
            (new TextField('name', 'Project Name')),
            (new TextField('version', 'Project Version')),
        ]);

        $helperSet = $this->getHelperSetMockWithInput(
            ['Demoooo', '8.x'],
            ['no']
        );
        $data = [];

        $this->form
            ->setHelperSet($helperSet)
            ->save(function ($results) use (&$data) {
                $data = $results;
            });

        $this->assertEmpty($data);
    }

    public function testInterativeFormGetResults()
    {
        $this->form->addFields([
            (new TextField('name', 'Project Name')),
            (new TextField('version', 'Project Version'))
                ->setRequired(false),
        ]);

        $helperSet = $this->getHelperSetMockWithInput([
            'Demoooo',
            "\n",
        ]);

        $this->form
            ->setHelperSet($helperSet)
            ->process();

        $this->assertCount(1, $this->form->getResults());
        $this->assertCount(2, $this->form->getResults(false));
    }

    protected function getHelperSet()
    {
        return new HelperSet([
            new FormHelper(),
        ]);
    }

    protected function getHelperSetMockWithInput()
    {
        $args = func_get_args();

        $helperSet = $this
            ->getMockBuilder('\Symfony\Component\Console\Helper\HelperSet')
            ->setMethods(['get'])
            ->getMock();

        $helperSet->expects($this->any())
            ->method('get')
            ->with($this->stringContains('question'))
            ->will($this->returnCallback(function () use ($args) {
                static $count = 0;

                $input = $args[$count];

                if (is_array($input)) {
                    $input = implode("\n", $input);
                }
                $helper = new QuestionHelper();

                $helper->setInputStream(
                    $this->getInputStream($input)
                );

                ++$count;

                return $helper;
            }));

        return $helperSet;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }
}
