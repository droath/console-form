<?php

namespace Droath\ConsoleForm;

use Symfony\Component\Finder\Finder;

/**
 * Define the form discovery class.
 */
class FormDiscovery
{
    /**
     *  Search depth.
     *
     * @var int
     */
    protected $depth;

    /**
     * Form discovery constructor.
     *
     * @param int $depth
     *   The directory structure depth to search.
     */
    public function __construct($depth = '< 3')
    {
        $this->depth = $depth;
    }

    /**
     * Run console form discovery.
     *
     * @param string|array $directory
     *   Input a single directory or an array of directories.
     * @param string $base_namespace
     *   The base namespace for the given class.
     *
     * @return array
     *   An array of forms that match the search criteria.
     */
    public function discover($directory, $base_namespace)
    {
        if (is_string($directory)) {
            $directory = [$directory];
        }

        return $this->doDiscovery($directory, $base_namespace);
    }

    /**
     * Do form discovery.
     *
     * @param array $directories
     *   An array of directories to search in.
     * @param string $base_namespace
     *   The base namespace for the given class.
     *
     * @return array
     *   An array of forms that match the search criteria.
     */
    protected function doDiscovery(array $directories, $base_namespace)
    {
        $forms = [];
        $filter = $this->filterByNamespace($base_namespace);

        foreach ($this->searchFiles($directories, $filter) as $file) {
            $ext = $file->getExtension();
            $classname = $base_namespace . '\\' . $file->getBasename(".$ext");

            if (!class_exists($classname)) {
                throw new \Exception(
                    'Missing class found during form discovery.'
                );
            }
            $instance = new $classname();

            if (!$instance instanceof FormInterface) {
                throw new \Exception(
                    sprintf('Form class (%s) is missing \Droath\ConsoleForm\Form\FormInterface.', $classname)
                );
            }

            $forms[$instance->getName()] = $instance->buildForm();
        }

        return $forms;
    }

    /**
     * Finder search files.
     *
     * @param array $directories
     *   An array of directories.
     * @param  $filter
     *   A callable filter function.
     *
     * @return \Symfony\Component\Finder\Finder
     *   The finder object.
     */
    protected function searchFiles(array $directories, $filter = null)
    {
        $finder = new Finder();
        $finder->files()
            ->name('*.php')
            ->in($directories)
            ->depth($this->depth);

        if (isset($filter) && is_callable($filter)) {
            $finder->filter($filter);
        }

        return $finder;
    }

    /**
     * Filter by class namespace.
     *
     * @param string $namespace
     *   The namespace to filter PHP classes.
     *
     * @return bool
     *   Return false if namespace doesn't match or does not exist.
     */
    protected function filterByNamespace($namespace)
    {
        return function ($file) use ($namespace) {
            $content = file_get_contents($file);
            $tokens = token_get_all($content);

            $count = count($tokens);
            $token_namespace = null;

            for ($i = 0; $i < $count; ++$i) {
                $token = $tokens[$i];

                if (is_array($token) && $token[0] === T_NAMESPACE) {
                    $token_namespace .= '\\';
                    while (++$i < $count) {
                        if ($tokens[$i] === ';') {
                            $namespace = trim($namespace);
                            break;
                        }
                        $token_namespace .= is_array($tokens[$i]) ? trim($tokens[$i][1]) : '';
                    }

                    if ($token_namespace !== $namespace) {
                        return false;
                    }

                    break;
                }
            }

            if (!isset($token_namespace)) {
                return false;
            }
        };
    }
}
