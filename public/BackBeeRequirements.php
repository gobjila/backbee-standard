<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class Requirement
{
    const LEVEL_OK = 0;
    const LEVEL_WARNING = 1;
    const LEVEL_ERROR = 2;

    /**
     * @var mixed
     */
    private $expected;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var integer
     */
    private $level;

    /**
     * @var boolean
     */
    private $installOnly;

    /**
     * Requirement's constructor
     */
    public function __construct($expected, $value, $title, $errorMessage, $installOnly = false, $level = null)
    {
        $this->expected = $expected;
        $this->value = $value;
        $this->title = $title;
        $this->errorMessage = $errorMessage;
        $this->installOnly = true === $installOnly;
        $this->level = $level ?: self::LEVEL_ERROR;
    }

    /**
     * @return boolean
     */
    public function isOk()
    {
        return $this->expected === $this->value;
    }

    /**
     * @return boolean
     */
    public function forInstallOnly()
    {
        return true === $this->installOnly;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }
}

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class BackBeeRequirements
{
    const REQUIRED_PHP_VERSION = '5.4.0';

    /**
     * @return array
     */
    public function getRequirements()
    {
        $requirements = [];

        $requirements[] = new Requirement(
            true,
            version_compare(phpversion(), self::REQUIRED_PHP_VERSION, '>='),
            'Version of PHP - required >= ' . self::REQUIRED_PHP_VERSION,
            'Your version of PHP is not compatible with BackBee. You must upgrade your PHP.'
        );

        $requirements[] = new Requirement(
            true,
            function_exists('token_get_all'),
            'PHP Tokenizer enable',
            'Your version of PHP was compiled with <em>--disable-tokenizer</em>, please recompile it without this option.'
        );

        $requirements[] = new Requirement(
            false,
            empty(ini_get('date.timezone')),
            'Default timezone set',
            'We strongly recommend you to set a default timezone<br/>(see <a href="http://php.net/manual/en/datetime.configuration.php#ini.date.timezone" target="_blank">http://php.net/manual/en/datetime.configuration.php#ini.date.timezone</a>).',
            false,
            Requirement::LEVEL_WARNING
        );

        $requirements[] = new Requirement(
            true,
            is_dir(realpath(__DIR__ . '/../vendor/composer')),
            'Dependencies installation',
            'You have to install BackBee\'s dependencies by running `composer.phar install` (https://getcomposer.org/)'
        );

        $cacheDirectory = realpath(__DIR__ . '/..') . '/cache';
        $requirements[] = new Requirement(
            true,
            is_dir($cacheDirectory) && is_writable($cacheDirectory) && is_readable($cacheDirectory),
            'Cache folder - readable and writable',
            "BackBee expected cache directory at `$cacheDirectory`; this directory must be readable and writable"
        );

        $logDirectory = realpath(__DIR__ . '/..') . '/log';
        $requirements[] = new Requirement(
            true,
            is_dir($logDirectory) && is_writable($logDirectory) && is_readable($logDirectory),
            'Log folder - readable and writable',
            "BackBee expected log directory at `$logDirectory`; this directory must be readable and writable"
        );

        $dataDirectory = realpath(__DIR__ . '/..') . '/repository/Data';
        $requirements[] = new Requirement(
            true,
            is_dir($dataDirectory) && is_writable($dataDirectory) && is_readable($dataDirectory),
            'Data folder - readable and writable',
            "BackBee expected data directory at `$dataDirectory`; this directory should be readable and writable to allow file uploads",
            false,
            Requirement::LEVEL_WARNING
        );

        $gdModule = 'gd';
        $requirements[] = new Requirement(
            true,
            extension_loaded($gdModule),
            'gd extension - installed',
            "Extension `$gdModule` should be installed to allow post-treatment after image upload",
            false,
            Requirement::LEVEL_WARNING
        );

        $configDirectory = realpath(__DIR__ . '/..') . '/repository/Config';
        $requirements[] = new Requirement(
            true,
            is_dir($configDirectory) && is_readable($configDirectory) && is_writable($configDirectory),
            'Configuration directory - writable',
            "BackBee's installer expected configuration directory located at `$configDirectory` to be readable and writable",
            true
        );

        $publicDirectory = __DIR__;
        $requirements[] = new Requirement(
            true,
            is_dir($publicDirectory) && is_readable($publicDirectory) && is_writable($publicDirectory),
            'Public directory - writable',
            "BackBee's installer expected public directory located at `$publicDirectory` to be readable and writable",
            true
        );

        return $requirements;
    }
}
