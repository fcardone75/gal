<?php


namespace App\Service\Import\Config;


use App\Service\Contracts\Import\Config\YamlFileLoaderInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;

class YamlFileLoader extends FileLoader implements YamlFileLoaderInterface
{
    private $yamlParser;

    public function load($resource, string $type = null)
    {
        $path = $this->locator->locate($resource);

        if (!stream_is_local($path)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $path));
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" not found.', $path));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        try {
            return $this->yamlParser->parseFile($path, Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML: ', $path).$e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null)
    {
        return \is_string($resource) && \in_array(pathinfo($resource, \PATHINFO_EXTENSION), ['yml', 'yaml'], true) && (!$type || 'yaml' === $type);
    }
}
