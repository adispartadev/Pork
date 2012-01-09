<?php

/**
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License, Version 3
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */

namespace Pork;

/**
 * This class is just to implement routines once - it's not really a base class, so don't rely on it, it has nothing to do with realy inheritance.
 *
 * It should be replaced with trait in PHP 5.4.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
abstract class SerializationHandler implements \Serializable
{
    /**
     * Produces plain array container with data.
     *
     * This method is used by ZendFramework during many serialization routines.
     *
     * @return array Plain array with data.
     * @version 0.0.1
     * @since 0.0.1
     */
    abstract public function toArray();

    /**
     * Produces JSON representaiton of object data.
     *
     * This method is used by ZendFramework during JSON serialization routines.
     *
     * @return string JSON representation of data.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function toJson()
    {
        // use ZendFramework is exists
        $data = $this->toArray();
        return \class_exists('Zend\\Json\\Json') ? \Zend\Json\Json::encode($data) : \json_encode($data);
    }

    /**
     * Produces string description of object.
     *
     * This method is used by ZendFramework during various output routines.
     *
     * @return string String description of object.
     * @version 0.0.1
     * @since 0.0.1
     */
    abstract public function toString();

    /**
     * Produces string description of object.
     *
     * Magic PHP5 call.
     *
     * @return string String description of object.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Recovers instance from plain data.
     *
     * @param array $data Data of object to restore.
     * @param SerializationHandler $instance Optionaly, target instance which should be recovered.
     * @return SerializationHandler Recovered instance.
     * @throws Exception\BadMethodCallException This method should be abstract, but is static - have to be sub-classed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public static function fromArray(array $data, SerializationHandler $instance = null)
    {
        throw new Exception\BadMethodCallException('Abstract static method SerializationHandler::fromArray() called.');
    }

    /**
     * Recovers instance from JSON data.
     *
     * @param string $data JSON data.
     * @param SerializationHandler $instance Optionaly, target instance which should be recovered.
     * @return SerializationHandler Recovered instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public static function fromJson($data, SerializationHandler $instance = null)
    {
        return static::fromArray(\class_exists('Zend\\Json\\Json') ? \Zend\Json\Json::decode($data, true) : \json_decode($data, true), $instance);
    }

    /**
     * Returns serialized data.
     *
     * @return string Serialized data array.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function serialize()
    {
        return \serialize($this->toArray());
    }

    /**
     * Unserializes object from stored data.
     *
     * @param string $data Serialized data.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function unserialize($data)
    {
        static::fromArray(\unserialize($data), $this);
    }
}
