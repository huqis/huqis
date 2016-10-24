<?php

namespace frame\library\helper;

use frame\library\exception\ReflectionTemplateException;

/**
 * Helper for PHP's reflection
 */
class ReflectionHelper {

    /**
     * Gets a property of the provided data
     * @param array|object $data Data container
     * @param string $name Name of the property
     * @param mixed $default Default value to be returned when the property
     * is not set
     * @return mixed Value of the property if found, null otherwise
     * @throws \frame\library\exception\ReflectionTemplateException
     */
    public function getProperty(&$data, $name, $default = null) {
        if (!is_string($name) || $name == '') {
            throw new ReflectionTemplateException('Could obtain property: invalid or empty name provided');
        }

        if (is_array($data)) {
            return $this->getArrayProperty($data, $name, $default);
        }

        $methodName = 'get' . ucfirst($name);
        if (method_exists($data, $methodName)) {
            return $data->$methodName();
        } elseif (strncmp($name, 'is', 2) === 0 && method_exists($data, $name)) {
            return $data->$name();
        }

        if (isset($data->$name)) {
            return $data->$name;
        }

        return $default;
    }

    /**
     * Gets the property of an array
     * @param array $data Data array
     * @param string $name Name of the property, can be something like name[sub]
     * @param mixed $default Default value when the property is not set
     * @return mixed
     * @throws \frame\library\exception\ReflectionTemplateException
     */
    private function getArrayProperty(array &$data, $name, $default = null) {
        $positionOpen = strpos($name, '[');
        if ($positionOpen === false) {
            return $this->getArrayValue($data, $name, $default);
        } elseif ($positionOpen === 0) {
            throw new ReflectionTemplateException('Could not get property ' . $name . ': name cannot start with [');
        }

        $tokens = explode('[', $name);

        $value = $data;
        $token = array_shift($tokens) . ']';
        while ($token != null) {
            $token = $this->parseArrayToken($token, $name);

            $value = $this->getArrayValue($value, $token);
            if ($value === null) {
                return $default;
            }

            $token = array_shift($tokens);
        }

        return $value;
    }

    /**
     * Gets a value from a array
     * @param array $array
     * @param string $key
     * @return null|mixed Value if the key was set, null otherwise
     */
    private function getArrayValue(array $array, $key, $default = null) {
        if (!isset($array[$key])) {
            return $default;
        }

        return $array[$key];
    }

    /**
     * Sets a property to the provided data
     * @param array|object $data Data container
     * @param string $name Name of the property
     * @param mixed $value Value for the property
     * @param boolean $useReflection Set to true to skip setter
     * @return null
     * @throws \frame\library\exception\ReflectionTemplateException
     */
    public function setProperty(&$data, $name, $value) {
        if (!is_string($name) || $name == '') {
            throw new ReflectionTemplateException('Could not set property: invalid or empty name provided');
        }

        if (is_array($data)) {
            return $this->setArrayProperty($data, $name, $value);
        }

        $methodName = 'set' . ucfirst($name);
        if (method_exists($data, $methodName)) {
            $data->$methodName($value);
        } else {
            $data->$name = $value;
        }
    }

    /**
     * Sets an array property
     * @param array $data
     * @param string $name
     * @param mixed $value
     * @throws \frame\library\exception\ReflectionTemplateException
     */
    private function setArrayProperty(array &$data, $name, $value) {
        $positionOpen = strpos($name, '[');
        if ($positionOpen === false) {
            if ($value !== null) {
                $data[$name] = $value;
            } elseif (isset($data[$name])) {
                unset($data[$name]);
            }

            return;
        } elseif ($positionOpen === 0) {
            throw new ReflectionTemplateException('Could not set property ' . $name . ': name cannot start with [');
        }

        $tokens = explode('[', $name);

        $array = &$data;
        $previousArray = &$array;

        $token = array_shift($tokens) . ']';
        $token = $this->parseArrayToken($token, $name);

        while (!empty($tokens)) {
            if (isset($array[$token]) && is_array($array[$token])) {
                $array = &$array[$token];
            } else {
                if ($value === null) {
                    return;
                }

                $previousArray[$token] = [];
                $array = &$previousArray[$token];
            }

            $previousArray = &$array;
            $token = $this->parseArrayToken(array_shift($tokens), $name);
        }

        if ($value !== null) {
            $array[$token] = $value;
        } elseif (isset($data[$name])) {
            unset($array[$token]);
        }
    }

    /**
     * Parses an array token, checks for a closing bracket at the end of the
     * token
     * @param string $token Token in the property
     * @param string $name Full property name
     * @return string Parsed name of the token
     * @throws \frame\library\exception\ReflectionTemplateException when an
     * invalid token has been provided
     */
    private function parseArrayToken($token, $name) {
        $positionClose = strpos($token, ']');
        if ($positionClose === false) {
            throw new ReflectionTemplateException('Array ' . $token . ' opened but not closed in ' . $name);
        }

        if ($positionClose != (strlen($token) - 1)) {
            throw new ReflectionTemplateException('Array not closed before the end of the token in ' . $name);
        }

        return substr($token, 0, -1);
    }

}
