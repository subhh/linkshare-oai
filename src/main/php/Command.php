<?php

declare(strict_types=1);

/**
 * This file is part of Linkshare OAI Webservice.
 *
 * Linkshare OAI Webservice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Linkshare OAI Webservice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Linkshare OAI Webservice.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace SubHH\Linkshare\OAI;

use HAB\OAI\PMH\Command\CommandInterface;

/**
 * Command base class.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
abstract class Command implements CommandInterface
{
    /** @var array<string, string> */
    private $parameters = array();

    /**
     * @var Mapper
     */
    protected $mapper;

    public function __construct (Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getParameters () : array
    {
        return $this->parameters;
    }

    public function setParameter (string $name, string $value) : void
    {
        $this->parameters[$name] = $value;
    }

    public function __get (string $name) : ?string
    {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        }
        return null;
    }

}
