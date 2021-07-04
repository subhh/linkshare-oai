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

use HAB\OAI\PMH\ProtocolError;
use HAB\OAI\PMH\Model\ResumptionToken;

/**
 * Resumable command.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
trait Resumable
{
    /**
     * @var int
     */
    public $cursor = 0;

    /**
     * @var int
     */
    private $itemsPerPage = 25;

    public function createResumptionToken (int $cursor, int $completeListSize) : ?ResumptionToken
    {
        if ($cursor + $this->itemsPerPage < $completeListSize) {
            $tokenValue = Token::encode($this);
            $token = new ResumptionToken($tokenValue);
            $token->setCursor($this->cursor);
            $token->setCompleteListSize($completeListSize);
            return $token;
        }
        return null;
    }

    public function resume (string $token) : void
    {
        if (Token::decode($this, $token) === false) {
            throw new ProtocolError\BadResumptionToken();
        }
        $this->cursor += $this->itemsPerPage;
    }
}
