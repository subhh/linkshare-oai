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

/**
 * Resumption token.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
abstract class Token
{
    static public function encode (object $command) : string
    {
        return base64_encode(http_build_query(get_object_vars($command)));
    }

    static public function decode (object $command, string $token) : bool
    {
        $token = base64_decode($token, true);
        if ($token === false) {
            return false;
        }

        parse_str($token, $data);
        $properties = get_object_vars($command);
        if (array_diff_key($data, $properties)) {
            return false;
        }

        foreach ($data as $key => $value) {
            $command->$key = $value;
        }
        return true;
    }
}
