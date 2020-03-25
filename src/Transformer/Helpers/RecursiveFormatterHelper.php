<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/24/15
 * Time: 9:05 PM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Api\Transformer\Helpers;

use NilPortugues\Serializer\Serializer;

final class RecursiveFormatterHelper
{
    /**
     * Given a class name will return its name without the namespace and
     * in under_score to be used as a key in an array.
     *
     * @param string $key
     *
     * @return string
     */
    public static function namespaceAsArrayKey(string $key) : string
    {
        $keys = \explode('\\', $key);
        $className = \end($keys);

        return self::camelCaseToUnderscore($className);
    }

    /**
     * Transforms a given string from camelCase to under_score style.
     *
     * @param string $camel
     * @param string $splitter
     *
     * @return string
     */
    public static function camelCaseToUnderscore(string $camel, string $splitter = '_') : string
    {
        $camel = \preg_replace(
            '/(?!^)[[:upper:]][[:lower:]]/',
            '$0',
            \preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $camel)
        );

        return \strtolower($camel);
    }

    /**
     * @param \NilPortugues\Api\Mapping\Mapping[] $mappings
     * @param array                               $value
     * @param string                              $type
     *
     * @return array
     */
    public static function getIdPropertyAndValues(array &$mappings, array &$value, string $type) : array
    {
        $values = [];
        $idProperties = self::getIdProperties($mappings, $type);

        foreach ($idProperties as &$propertyName) {
            if (!empty($value[$propertyName])) {
                $values[] = self::getIdValue($value[$propertyName]);
                $propertyName = \sprintf('{%s}', $propertyName);
            }
        }
        self::flattenObjectsWithSingleKeyScalars($values);

        return [$values, $idProperties];
    }

    /**
     * @param \NilPortugues\Api\Mapping\Mapping[] $mappings
     * @param string                              $type
     *
     * @return array
     */
    public static function getIdProperties(array &$mappings, string $type)
    {
        $idProperties = [];

        if ((is_string($type) || is_integer($type) || is_float($type) || is_bool($type)) && !empty($mappings[$type])) {
            $idProperties = $mappings[$type]->getIdProperties();
        }

        return $idProperties;
    }

    /**
     * @param array $id
     *
     * @return array
     */
    public static function getIdValue(array $id)
    {
        self::formatScalarValues($id);
        if (\is_array($id)) {
            RecursiveDeleteHelper::deleteKeys($id, [Serializer::CLASS_IDENTIFIER_KEY]);
        }

        return $id;
    }

    /**
     * Replaces the Serializer array structure representing scalar
     * values to the actual scalar value using recursion.
     *
     * @param array $array
     */
    public static function formatScalarValues(array &$array)
    {
        $array = self::arrayToScalarValue($array);

        if (\is_array($array) && !array_key_exists(Serializer::SCALAR_VALUE, $array)) {
            self::loopScalarValues($array, 'formatScalarValues');
        }
    }

    /**
     * @param array $array
     *
     * @return array
     */
    protected static function arrayToScalarValue(array &$array)
    {
        if (isset($array[Serializer::SCALAR_VALUE])) {
            $array = $array[Serializer::SCALAR_VALUE];
        }

        return $array;
    }

    /**
     * @param array  $array
     * @param string $method
     */
    protected static function loopScalarValues(array &$array, string $method)
    {
        foreach ($array as $key => &$value) {
            if (\is_array($value)) {
                if (!empty($value)) {
                    self::$method($value);
                } else {
                    unset($array[$key]);
                }
            }
        }
    }

    /**
     * Simplifies the data structure by removing an array level if data is scalar and has one element in array.
     *
     * @param array $array
     */
    public static function flattenObjectsWithSingleKeyScalars(array &$array)
    {
        if (1 === \count($array) && \is_scalar(\end($array))) {
            $array = \array_pop($array);
        }

        if (\is_array($array)) {
            self::loopScalarValues($array, 'flattenObjectsWithSingleKeyScalars');
        }
    }
}
