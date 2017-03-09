<?php

namespace Droath\ConsoleForm\Tests;

use Droath\ConsoleForm\Field\BooleanField;
use Droath\ConsoleForm\Field\SelectField;
use Droath\ConsoleForm\Field\TextField;
use Droath\ConsoleForm\Form;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
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

    /**
     * Console input.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * Console output.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    protected function setUp()
    {
        $this->form = new Form();
        $this->input = new ArrayInput([]);
        $this->output = new NullOutput();
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
        $helper = $this->setQuestionInputStreamFromArray([
            'Steve Jobs',
        ]);

        $results = $this->form->process($this->input, $this->output, $helper);

        $this->assertContains('Steve Jobs', $results['name']);
    }

    public function testInterativeBooleanFieldProcess()
    {
        $this->form->addFields([
            new BooleanField('like_your_job', 'Do you like your job?'),
            new BooleanField('like_your_location', 'Do you like your current location?'),
        ]);

        $helper = $this->setQuestionInputStreamFromArray([
            'yes',
            'no',
        ]);

        $results = $this->form->process($this->input, $this->output, $helper);

        $this->assertEquals('true', $results['like_your_job']);
        $this->assertEquals('false', $results['like_your_location']);
    }

    public function testInterativeSelectFieldProcess()
    {
        $this->form->addFields([
            (new SelectField('favorite_color', 'Favorite color?'))
                ->setOptions(['red', 'blue', 'green']),
        ]);

        $helper = $this->setQuestionInputStreamFromArray([
            'blue',
        ]);

        $results = $this->form->process($this->input, $this->output, $helper);

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

        $helper = $this->setQuestionInputStreamFromArray([
            'blue',
        ]);

        $this->form->process($this->input, $this->output, $helper);

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

        $helper = $this->getHelperSet()->get('question');
        $helper->setInputStream($this->getInputStream("\n"));

        $this->form->process($this->input, $this->output, $helper);

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
        $helper = $this->getHelperSet()->get('question');
        $helper->setInputStream($this->getInputStream("\n\n\n"));

        $results = $this->form->process($this->input, $this->output, $helper);

        $this->assertEquals('false', $results['happiness']);
        $this->assertEquals('green', $results['favorite_color']);
        $this->assertEquals('Demo Project', $results['project_name']);
    }

    public function testIneractiveSetNormalizer()
    {
        $this->form->addFields([
            (new TextField('project_name', 'Project Name'))
                ->setNormalizer(function ($value) {
                    return strtr(strtolower($value), ' ', '-');
                }),
        ]);

        $helper = $this->setQuestionInputStreamFromArray([
            'Hacker Box',
        ]);

        $results = $this->form->process(
            $this->input, $this->output, $helper
        );

        $this->assertEquals('hacker-box', $results['project_name']);
    }

    public function testIneractiveSetSubform()
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

        $helper = $this->setQuestionInputStreamFromArray([
            'yes',
            1000,
            'cave',
        ]);

        $results = $this->form->process($this->input, $this->output, $helper);

        $this->assertCount(2, $results['questions']);
        $this->assertEquals(1000, $results['questions']['how_old']);
        $this->assertEquals('cave', $results['questions']['location']);

    }

    public function testIneractiveSetCondition()
    {
        $this->form->addFields([
            (new TextField('project_name', 'Project Name')),
            (new TextField('project_version', 'Project Version'))
                ->setCondition('project_name', 'Demo'),
        ]);

        $helper = $this->setQuestionInputStreamFromArray([
            'Demoooo',
            '8.x',
        ]);

        $results = $this->form->process($this->input, $this->output, $helper);

        $this->assertEquals('Demoooo', $results['project_name']);
        $this->assertArrayNotHasKey('project_version', $results);
    }

    protected function getHelperSet()
    {
        return new HelperSet([
            new FormatterHelper(),
            new QuestionHelper(),
        ]);
    }

    protected function setQuestionInputStreamFromArray(array $array)
    {
        $input = implode("\n", $array);
        $helper = $this->getHelperSet()->get('question');

        $helper->setInputStream(
            $this->getInputStream($input)
        );

        return $helper;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }
}
