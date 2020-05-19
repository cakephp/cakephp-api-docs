<?php
declare(strict_types=1);

namespace Cake\ApiDocs\Util;

use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Php\File;
use phpDocumentor\Reflection\Php\ProjectFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * SourceLoader
 */
class SourceLoader
{
    /**
     * @var string
     */
    protected $sourceDir;

    /**
     * @var \phpDocumentor\Reflection\Php\ProjectFactory
     */
    protected $factory;

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $classLoader;

    /**
     * @var \phpDocumentor\Reflection\Php\File[]
     */
    protected $projectFiles = [];

    /**
     * @var \phpDocumentor\Reflection\Php\Namespace_[]
     */
    protected $projectNamespaces = [];

    /**
     * @var \phpDocumentor\Reflection\Php\File[]
     */
    protected $vendorFiles = [];

    /**
     * @param string $sourceDir Source directory
     */
    public function __construct(string $sourceDir)
    {
        $this->sourceDir = $sourceDir;
        $this->factory = ProjectFactory::createInstance();
        $this->findClassLoader();
        $this->loadReflection();
    }

    /**
     * @return \phpDocumentor\Reflection\Php\File[]
     */
    public function getFiles(): array
    {
        return $this->projectFiles;
    }

    /**
     * @return \phpDocumentor\Reflection\Php\Namespace_[]
     */
    public function getNamespaces(): array
    {
        return $this->projectNamespaces;
    }

    /**
     * @param string $fqsen fqsen
     * @return \Cake\ApiDocs\Util\LoadedFqsen|null
     */
    public function find(string $fqsen): ?LoadedFqsen
    {
        $parentFqsen = null;
        $parts = explode('::', $fqsen);
        if (count($parts) > 1) {
            $parentFqsen = current($parts);
        }

        /** @var \phpDocumentor\Reflection\Php\File $file */
        [$file, $inProject] = $this->findFile($parentFqsen ?? $fqsen);
        if (!$file) {
            foreach ($this->projectFiles as $file) {
                foreach (['constants', 'functions'] as $type) {
                    $element = $file->{'get' . $type}()[$fqsen] ?? null;
                    if ($element) {
                        return new LoadedFqsen($fqsen, $element, null, $file, true);
                    }
                }
            }

            return null;
        }

        if ($parentFqsen) {
            $parentElement = $this->findClassLike($file, $parentFqsen);
            foreach (['methods', 'constants', 'properties'] as $type) {
                if (method_exists($parentElement, 'get' . $type)) {
                    $element = $parentElement->{'get' . $type}()[$fqsen] ?? null;
                    if ($element) {
                        return new LoadedFqsen($fqsen, $element, $parentElement, $file, $inProject);
                    }
                }
            }

            return null;
        }

        foreach (['classes', 'interfaces', 'traits', 'constants', 'functions'] as $type) {
            $element = $file->{'get' . $type}()[$fqsen] ?? null;
            if ($element) {
                return new LoadedFqsen($fqsen, $element, null, $file, $inProject);
            }
        }

        return null;
    }

    /**
     * @param \phpDocumentor\Reflection\Php\File $file The reflection file
     * @param string $fqsen The class fqsen
     * @return \phpDocumentor\Reflection\Element|null
     */
    protected function findClassLike(File $file, string $fqsen): ?Element
    {
        foreach (['classes', 'interfaces', 'traits'] as $type) {
            $class = $file->{'get' . $type}()[$fqsen] ?? null;
            if ($class) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @param string $fqsen A loadable fqsen
     * @return array
     */
    protected function findFile(string $fqsen): array
    {
        if ($fqsen[0] === '\\') {
            $fqsen = substr($fqsen, 1);
        }

        $path = $this->classLoader->findFile($fqsen);
        if ($path === false) {
            return [null, false];
        }

        $path = realpath($path);
        if (isset($this->projectFiles[$path])) {
            return [$this->projectFiles[$path], true];
        }

        if (empty($this->vendorFiles[$path])) {
            $tmpProject = $this->factory->create('tmp', [new LocalFile($path)]);
            $file = current($tmpProject->getFiles());
            $this->vendorFiles[$path] = $file;
        }

        return [$this->vendorFiles[$path], false];
    }

    /**
     * @return void
     */
    protected function findClassLoader(): void
    {
        // try to find vendor/ relative to sourceDir
        $vendorDir = $this->sourceDir;
        foreach (range(0, 2) as $parentNum) {
            $autoloadPath = $vendorDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (file_exists($autoloadPath)) {
                api_log('notice', "Found vendor autoload at {$autoloadPath}.");
                $this->classLoader = require $autoloadPath;
                $this->classLoader->unregister();
                break;
            }
            $vendorDir = dirname($vendorDir);
        }

        if ($this->classLoader === null) {
            api_log('notice', 'No vendor autoload found. Dependencies will not be parsed.');
        }
    }

    /**
     * @return void
     */
    protected function loadReflection(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir),
        );

        $files = [];
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            if (preg_match('/^.+\.php$/i', $entry->getFilename())) {
                $files[] = new LocalFile((string)$entry);
            }
        }

        $project = $this->factory->create('project', $files);
        $this->projectNamespaces = $project->getNamespaces();

        foreach ($project->getFiles() as $path => $file) {
            $this->projectFiles[realpath($path)] = $file;
        }
    }
}
